<template>
	<v-dialog v-model="value" scrollable persistent width="1000px">
		<!-- External dictionary import -->
		<admin-external-dictionary-import
			v-if="selectedDictionaryType === 'custom'"
			:language="language"
			@import-finished="importFinished"
			@back-to-dictionaries="backToDictionaries"
			@close="close"
		></admin-external-dictionary-import>

		<!-- Supported dictionary import -->
		<admin-supported-dictionary-import
			v-if="selectedDictionaryType === 'supported'"
			@import-finished="importFinished"
			@back-to-dictionaries="backToDictionaries"
			@close="close"
		></admin-supported-dictionary-import>

		<!-- Deepl dictionary creation -->
		<admin-dictionary-api-import
			v-if="['deepl', 'mymemory', 'libretranslate', 'customapi'].includes(selectedDictionaryType)"
			:language="$props.language"
			:selected-dictionary-type="selectedDictionaryType"
			@import-finished="importFinished"
			@back-to-dictionaries="backToDictionaries"
			@close="close"
		></admin-dictionary-api-import>

		<!-- Dictionary selection list -->
		<v-card
			v-if="selectedDictionaryType === null"
			id="supported-dictionary-import-dialog"
			class="rounded-lg"
			min-height="600px"
		>
			<!-- Title bar -->
			<v-card-title>
				<span class="text-h5">Dictionary import</span>
				<v-spacer></v-spacer>
				<v-btn icon @click="close">
					<v-icon>mdi-close</v-icon>
				</v-btn>
			</v-card-title>

			<!-- Dictionary list -->
			<v-card-text>
				<!-- Dictionary type selection -->
				<label class="font-weight-bold mt-4"></label>
				<v-radio-group v-model="dictionaryTypeInput" class="mt-0">
					<v-radio
						label="Supported dictionary file from the user manual"
						value="supported"
					></v-radio>
					<v-radio label="Custom .csv dictionary file" value="custom"></v-radio>
					<v-radio label="DeepL dictionary" value="deepl"></v-radio>
					<v-radio label="MyMemory dictionary" value="mymemory"></v-radio>
					<v-radio label="LibreTranslate dictionary" value="libretranslate"></v-radio>
					<v-radio label="Custom API dictionary" value="customapi"></v-radio>
				</v-radio-group>
			</v-card-text>
			<v-card-actions>
				<v-spacer />
				<v-btn rounded depressed color="primary" @click="selectDictionary">Continue</v-btn>
			</v-card-actions>
		</v-card>
	</v-dialog>
</template>

<script>
export default {
	props: {
		value: Boolean,
		language: String,
	},
	emits: ['input'],
	data: function () {
		return {
			dictionaryTypeInput: 'supported',
			selectedDictionaryType: null,
		}
	},
	mounted: function () {},
	methods: {
		selectDictionary() {
			this.selectedDictionaryType = this.dictionaryTypeInput
		},
		backToDictionaries() {
			this.dictionaryTypeInput = 'supported'
			this.selectedDictionaryType = null
		},
		close() {
			this.$emit('input', false)
		},
		importFinished() {
			this.$emit('import-finished')
		},
	},
}
</script>
