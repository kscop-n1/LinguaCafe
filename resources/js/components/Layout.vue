<template>
	<v-app :class="{ eink: theme == 'eink', dark: theme == 'dark' }">
		<template v-if="loading">
			<div id="app-loader" class="w-100 h-full d-flex justify-center items-center">
				<v-progress-linear height="4" class="mb-5" indeterminate />
			</div>
		</template>

		<template v-else>
			<!-- Dialogs -->
			<start-review-dialog v-model="startReviewDialog" />
			<logout-dialog v-model="logoutDialog" />

			<template v-if="$router.currentRoute.path !== '/login'">
				<theme-selection-dialog
					v-model="themeSelectionDialog"
					@input="updateTheme"
				></theme-selection-dialog>
				<language-selection-dialog v-model="languageSelectionDialog"></language-selection-dialog>
				<v-navigation-drawer
					id="navigation-drawer"
					app
					dense
					:class="{ eink: theme == 'eink' }"
					:mini-variant="$vuetify.breakpoint.md || navbarCollapsed"
					:permanent="$vuetify.breakpoint.mdAndUp"
					v-model="drawer"
					color="foreground"
				>
					<!-- Logo -->
					<div
						id="logo"
						class="d-flex justify-center my-5"
						v-if="$vuetify.breakpoint.lgAndUp && !navbarCollapsed"
					>
						<img src="/icon512rounded.png" class="mr-2" width="32px" height="32px" />
						<span class="text--text">Lingua Cafe</span>
					</div>

					<v-list nav shaped dense class="pl-0">
						<!-- Navigation buttons -->
						<v-list-item
							class="navigation-button"
							v-for="(item, index) in navigation"
							:key="index"
							:to="item.url"
							@click="navigationClick(item.name, $event)"
						>
							<v-icon> {{ item.icon }} </v-icon>
							<span class="pl-6"> {{ item.name }} </span>
						</v-list-item>
						<v-list-item class="navigation-button" @click="openLogoutDialog">
							<v-icon> mdi-logout </v-icon>
							<span class="pl-6"> Logout </span>
						</v-list-item>
					</v-list>

					<template v-slot:append>
						<!-- Large navigation drawer -->
						<template v-if="!$vuetify.breakpoint.md && !navbarCollapsed">
							<v-list nav shaped dense class="pl-0">
								<!-- Navigation buttons -->
								<v-list-item class="navigation-button" @click="collapseNavbar">
									<v-icon> mdi-arrow-collapse-left </v-icon>
									<span class="pl-6"> Hide</span>
								</v-list-item>
								<v-list-item class="navigation-button" @click="themeSelectionDialog = true">
									<v-icon> mdi-palette </v-icon>
									<span class="pl-6"> Theme</span>
								</v-list-item>
								<v-list-item class="navigation-button" @click="languageSelectionDialog = true">
									<v-img
										class="border"
										:src="'/images/flags/' + selectedLanguage.toLowerCase() + '.png'"
										max-width="26"
										height="17"
									></v-img>
									<span class="pl-5"> Language</span>
								</v-list-item>
							</v-list>
						</template>

						<!-- Mini navigation drawer -->
						<template v-else>
							<v-btn
								v-if="$vuetify.breakpoint.lgAndUp"
								id="collapse"
								rounded
								text
								class="mini-drawer-button"
								@click="expandNavbar"
								title="Expand sidebar"
							>
								<v-icon>mdi-arrow-collapse-right</v-icon>
							</v-btn>
							<v-btn
								id="theme"
								rounded
								text
								class="mini-drawer-button"
								@click="themeSelectionDialog = true"
								title="Theme"
							>
								<v-icon>mdi-palette</v-icon>
							</v-btn>
							<v-btn
								id="language"
								rounded
								text
								class="mini-drawer-button"
								@click="languageSelectionDialog = true"
								title="Select language"
							>
								<v-img
									:src="'/images/flags/' + selectedLanguage.toLowerCase() + '.png'"
									max-width="31"
									height="20"
								></v-img>
							</v-btn>
						</template>
					</template>
				</v-navigation-drawer>

				<!-- Bottom navigation -->
				<v-bottom-navigation
					dense
					grow
					shift
					class="d-flex d-sm-flex d-md-none"
					dark
					background-color="primary"
				>
					<v-btn class="text-decoration-none" width="60" style="float: left" @click="drawer = true">
						<span>More</span>
						<v-icon>mdi-menu</v-icon> </v-btn
					><v-spacer></v-spacer>
					<v-btn
						class="text-decoration-none"
						grow
						v-for="(item, index) in navigation"
						:key="index"
						:to="item.url"
						v-if="item.bottomNav"
					>
						<span>{{ item.name }}</span>
						<v-icon>{{ item.icon }}</v-icon>
					</v-btn>
				</v-bottom-navigation>
			</template>
			<v-main
				:style="{ background: $vuetify.theme.currentTheme.background, ...textStyling }"
				:class="{ eink: theme == 'eink' }"
			>
				<router-view
					:user-count="userCount"
					:language="selectedLanguage"
					:key="$route.fullPath"
				></router-view>
			</v-main>
		</template>
	</v-app>
</template>

<script>
import ThemeService from './../services/ThemeService'
import TextStylingService from './../services/TextStylingService'
import FontTypeService from './../services/FontTypeService'
import { DefaultLocalStorageManager } from './../services/LocalStorageManagerService'

export default {
	data: function () {
		return {
			userCount: null,
			loading: true,
			selectedLanguage: null,
			theme: DefaultLocalStorageManager.loadSetting('theme') || 'light',
			logoutDialog: false,
			themeSelectionDialog: false,
			languageSelectionDialog: false,
			startReviewDialog: false,
			drawer: false,
			navbarVisible: true,
			navbarCollapsed: false,
			navigation: [
				{
					name: 'Home',
					url: '/',
					icon: 'mdi-home',
					bottomNav: true,
				},
				{
					name: 'Library',
					url: '/books',
					icon: 'mdi-bookshelf',
					bottomNav: true,
				},
				{
					name: 'Vocabulary',
					url: '/vocabulary/search',
					icon: 'mdi-translate',
					bottomNav: true,
				},
				{
					name: 'Review',
					url: '',
					click: this.openStartReviewDialog,
					icon: 'mdi-playlist-check',
					bottomNav: false,
				},
				{
					name: 'User settings',
					url: '/user-settings',
					icon: 'mdi-account-cog',
					bottomNav: false,
				},
				{
					name: 'User manual',
					url: '/user-manual',
					icon: 'mdi-account-question',
					bottomNav: false,
				},
			],
		}
	},
	computed: {
		textStyling: function () {
			let settingsObject = this.$store.state.shared.textStylingSettings

			if (settingsObject === null) {
				settingsObject = TextStylingService.getDefaultTextStylingSettings()
			}

			const settingsCssObject = TextStylingService.getTextStylingSettingsObject(settingsObject)
			return settingsCssObject[this.theme]
		},
	},
	props: {},
	beforeMount() {
		this.initializeThemes()
	},
	mounted() {
		axios.get('/user/data').then(response => {
			console.log('user data loaded', response.data)
			if (response.data.userUuid === undefined) {
				this.userCount = response.data.userCount
				this.initializeThemes(undefined)
			}

			this.selectedLanguage = response.data.language
			this.userCount = response.data.userCount

			// set store data
			this.$store.commit('shared/setUuid', response.data.userUuid)
			this.$store.commit('shared/setUserName', response.data.userName)
			this.$store.commit('shared/setUserEmail', response.data.userEmail)
			this.$store.commit('shared/setUserAdmin', response.data.isAdmin)

			if (response.data.language == 'japanese') {
				this.navigation.splice(3, 0, {
					name: 'Kanji',
					url: '/kanji/search',
					icon: 'mdi-ideogram-cjk',
					bottomNav: false,
				})
			}

			if (this.$store.getters['shared/userAdmin']) {
				this.navigation.push({
					name: 'Admin settings',
					url: '/admin',
					icon: 'mdi-shield-lock',
					bottomNav: false,
				})
			}

			this.initializeThemes(response.data.themeSettings)

			// Watch OS theme change. Currently disabled to
			// const preferredDarkTheme = window.matchMedia("(prefers-color-scheme: dark)");
			// preferredDarkTheme.addEventListener("change", this.loadSelectedTheme);

			// load navbar status
			const savedNavbarCollapsed = DefaultLocalStorageManager.loadSetting('navbar-collapsed')
			this.navbarCollapsed = savedNavbarCollapsed ? savedNavbarCollapsed === 'true' : false

			this.loadFontTypeService()
			this.loading = false
		})
	},
	methods: {
		loadFontTypeService() {
			// load default and selected font types into the dom
			var fontTypeService = new FontTypeService(this.selectedLanguage, () => {
				fontTypeService.loadSelectedFontTypeIntoDom()
				fontTypeService.loadDefaultFontTypeIntoDom()
			})
		},
		initializeThemes(themeSettings) {
			this.loadSelectedTheme()
			ThemeService.setDefaultVuetifyTheme(this.$vuetify)

			if (themeSettings?.vuetifyThemes) {
				this.$store.commit('shared/setVuetifyThemeSettings', themeSettings.vuetifyThemes)
				this.$store.commit('shared/setTextStylingSettings', themeSettings.textStyling)
			}

			ThemeService.setVuetifyTheme(this.$vuetify, this.$store)
		},
		loadSelectedTheme() {
			const autoEnabled = ThemeService.isAuto()
			const preferredDarkTheme = window.matchMedia('(prefers-color-scheme: dark)')

			if (autoEnabled) {
				// auto-select user's system theme if 'auto' is enabled
				if (preferredDarkTheme.matches) {
					this.theme = 'dark'
					console.log('auto dark')
				} else {
					this.theme = 'light'
					console.log('auto light')
				}

				DefaultLocalStorageManager.saveSetting('theme', this.theme)
			} else {
				// otherwise use saved theme
				const savedTheme = DefaultLocalStorageManager.loadSetting('theme')
				this.theme = savedTheme ? savedTheme : 'light'
			}
		},
		collapseNavbar() {
			this.navbarCollapsed = true
			DefaultLocalStorageManager.saveSetting('navbar-collapsed', this.navbarCollapsed)
		},
		expandNavbar() {
			this.navbarCollapsed = false
			DefaultLocalStorageManager.saveSetting('navbar-collapsed', this.navbarCollapsed)
		},
		navigationClick(itemName, event) {
			if (itemName === 'Review') {
				this.startReviewDialog = true
				event.preventDefault()
			}

			// clicked on user manual
			if (itemName === 'User manual' && this.$router.currentRoute.path !== '/user-manual') {
				this.$router.push({ path: '/user-manual', replace: true })
			}
		},
		updateTheme() {
			const savedTheme = DefaultLocalStorageManager.loadSetting('theme')
			this.theme = savedTheme ? savedTheme : 'light'
		},
		openLogoutDialog() {
			this.logoutDialog = true
		},
	},
}
</script>
