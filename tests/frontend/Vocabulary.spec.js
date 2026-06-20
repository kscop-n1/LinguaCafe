import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import Vocabulary from '../../resources/js/components/Vocabulary/Vocabulary.vue';

function deferred() {
    let resolve;
    let reject;

    const promise = new Promise((resolvePromise, rejectPromise) => {
        resolve = resolvePromise;
        reject = rejectPromise;
    });

    return { promise, resolve, reject };
}

function responseFor(bookId, bookName, word) {
    return {
        data: {
            bookIndex: 0,
            books: [{ id: bookId, name: bookName, chapters: [] }],
            currentPage: 1,
            languageSpaces: true,
            pageCount: 1,
            wordCount: 1,
            words: [{
                id: bookId,
                stage: 2,
                translation: `${word} definition`,
                type: 'word',
                word,
            }],
        },
    };
}

function mountVocabulary(requests) {
    vi.stubGlobal('axios', {
        post: vi.fn(() => requests.shift().promise),
    });

    return mount(Vocabulary, {
        attachTo: document.body,
        props: {
            language: 'spanish',
        },
        global: {
            mocks: {
                $route: {
                    params: {
                        book: '101',
                        chapter: '-1',
                        orderBy: 'words',
                        page: '1',
                        phrases: 'both',
                        stage: '-999',
                        text: 'anytext',
                        translation: 'any',
                    },
                },
                $router: {
                    push: vi.fn(),
                },
            },
        },
    });
}

async function startNewerBookRequest(wrapper) {
    wrapper.vm.filters.book = 202;
    wrapper.vm.filters.bookIndex = 0;
    wrapper.vm.filters.chapter = -1;
    wrapper.vm.loadVocabularySearchPage();
    await wrapper.vm.$nextTick();
}

describe('Vocabulary stale response protection', () => {
    beforeEach(() => {
        document.body.innerHTML = '<div id="app"></div>';
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        document.body.innerHTML = '';
    });

    it('keeps the newer book result when the older success resolves last', async () => {
        const siloRequest = deferred();
        const candelaRequest = deferred();
        const wrapper = mountVocabulary([siloRequest, candelaRequest]);

        expect(axios.post).toHaveBeenNthCalledWith(
            1,
            '/vocabulary/search',
            expect.objectContaining({ book: 101 })
        );

        await startNewerBookRequest(wrapper);

        expect(wrapper.vm.filters.book).toBe(202);
        expect(axios.post).toHaveBeenNthCalledWith(
            2,
            '/vocabulary/search',
            expect.objectContaining({ book: 202 })
        );

        candelaRequest.resolve(responseFor(202, 'Candela Obscure', 'candela-word'));
        await flushPromises();

        expect(wrapper.vm.loading).toBe(false);
        expect(wrapper.vm.filters.book).toBe(202);
        expect(wrapper.vm.words.map(({ word }) => word)).toEqual(['candela-word']);
        expect(wrapper.text()).toContain('candela-word');

        siloRequest.resolve(responseFor(101, 'Silo', 'silo-word'));
        await flushPromises();

        expect(wrapper.vm.loading).toBe(false);
        expect(wrapper.vm.filters.book).toBe(202);
        expect(wrapper.vm.words.map(({ word }) => word)).toEqual(['candela-word']);
        expect(wrapper.text()).toContain('candela-word');
        expect(wrapper.text()).not.toContain('silo-word');

        wrapper.unmount();
    });

    it('ignores an older error after the newer book request succeeds', async () => {
        const siloRequest = deferred();
        const candelaRequest = deferred();
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
        const wrapper = mountVocabulary([siloRequest, candelaRequest]);

        await startNewerBookRequest(wrapper);

        candelaRequest.resolve(responseFor(202, 'Candela Obscure', 'candela-word'));
        await flushPromises();

        expect(wrapper.vm.loading).toBe(false);
        expect(wrapper.vm.filters.book).toBe(202);
        expect(wrapper.vm.words.map(({ word }) => word)).toEqual(['candela-word']);

        siloRequest.reject({
            response: {
                status: 500,
            },
        });
        await flushPromises();

        expect(wrapper.vm.loading).toBe(false);
        expect(wrapper.vm.filters.book).toBe(202);
        expect(wrapper.vm.words.map(({ word }) => word)).toEqual(['candela-word']);
        expect(wrapper.text()).toContain('candela-word');
        expect(consoleError).not.toHaveBeenCalled();

        wrapper.unmount();
    });
});
