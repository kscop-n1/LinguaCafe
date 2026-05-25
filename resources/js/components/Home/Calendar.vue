<template>
    <div>
        <div class="subheader subheader-margin-top d-flex">
            Calendar
            <v-spacer></v-spacer>

            <!-- Displayed goal select -->
            <v-menu location="bottom" :offset="8" class="rounded-lg">
                <template v-slot:activator="{ props }">
                    <v-btn class="calendar-button" color="foreground" rounded variant="flat" v-bind="props">
                        <span id="goal-selection-text-small">Goal</span>
                        <span id="goal-selection-text">Displayed goal</span>
                        <v-icon v-if="props?.['aria-expanded'] === 'true' ">mdi-chevron-up</v-icon>
                        <v-icon v-if="props?.['aria-expanded'] !== 'true'">mdi-chevron-down</v-icon>
                    </v-btn>
                </template>
                <v-list class="pa-0" density="compact">
                    <v-list-item @click="selectedGoal = 'reviews_due'; updateCalendar();">
                        <v-list-item-title>Reviews due</v-list-item-title>
                    </v-list-item>
                    <v-list-item @click="selectedGoal = 'read_words'; updateCalendar();">
                        <v-list-item-title>Reading</v-list-item-title>
                    </v-list-item>
                    <v-list-item @click="selectedGoal = 'review'; updateCalendar();">
                        <v-list-item-title>Review</v-list-item-title>
                    </v-list-item>
                    <v-list-item @click="selectedGoal = 'learn_words'; updateCalendar();">
                        <v-list-item-title>New words</v-list-item-title>
                    </v-list-item>
                </v-list>
            </v-menu>

            <!-- Date picker -->
            <v-menu
                v-model="showDatePicker"
                width="290px"
                location="bottom end"
                :offset="8"
                content-class="date-picker-dialog"
            >
                <template v-slot:activator="{ props }">
                    <v-btn
                        id="calendar-date-button"
                        class="calendar-button ml-2"
                        color="foreground"
                        rounded
                        v-bind="props"
                        @click="showDatePicker = true;"
                    >
                        <span id="calendar-date-button-text">{{ pickerDateFormated }}&nbsp;</span><v-icon>mdi-calendar</v-icon>
                    </v-btn>
                </template>
                <v-date-picker
                    v-model="pickerDate"
                    scrollable
                    type="month"
                    :show-current="false"
                    color="primary"
                    header-color="primary"
                    @change="datePickerChanged"
                >
                    <v-spacer></v-spacer>
                    <v-btn text rounded @click="showDatePicker = false">Close</v-btn>
                </v-date-picker>
            </v-menu>
        </div>

        <!-- calendar -->
        <v-card variant="outlined" id="calendar" class="rounded-lg pa-4 pt-0" :loading="popupMenu.saving">
            <!-- Calendar popup -->
            <v-menu
                content-class="calendar-popup-menu rounded-lg"
                v-model="popupMenu.active"
                :target="[popupMenu.x, popupMenu.y]"
                location-strategy="connected"
                location="bottom start"
                :offset="8"
                :close-on-click="false"
                :close-on-content-click="false"
            >
                <!-- Calendar popup date and reviews due-->
                <div id="calendar-popup-date" class="px-3 py-1" v-if="popupMenu.day" @click.stop=";">
                    <span id="calendar-popup-date-text">{{ popupMenu.day.fullDate }}</span>
                    <v-spacer></v-spacer>
                    <span id="calendar-popup-reviews-due">
                        <v-btn icon theme="dark" @click.stop="popupMenu.tab = 1;" v-if="popupMenu.tab == 0">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn icon theme="dark" @click.stop="popupMenu.tab = 0;" v-if="popupMenu.tab == 1">
                            <v-icon>mdi-arrow-left</v-icon>
                        </v-btn>
                        <v-btn icon theme="dark" @click="popupMenu.active = false;">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </span>
                </div>

                <!-- Calendar popup achievements-->
                <div id="calendar-popup-achievements" class="pa-3" v-if="popupMenu.day" @click.stop=";">
                    <v-window v-model="popupMenu.tab">
                        <!-- Popup menu info -->
                        <v-window-item :value="0">
                            <v-table density="compact" class="no-row-border no-hover">
                                <tbody>
                                    <tr>
                                        <td>Reviews due:</td>
                                        <td>{{ popupMenu.day.reviewsDue }}</td>
                                    </tr>
                                    <tr v-for="(achievement, index) in popupMenu.achievements" :key="index">
                                        <td>{{ goalTexts[achievement.type] }}:</td>
                                        <td v-if="achievement.goalQuantity">{{ achievement.achievedQuantity }}/{{ achievement.goalQuantity }}</td>
                                        <td v-if="!achievement.goalQuantity"> none </td>
                                    </tr>
                                </tbody>
                            </v-table>
                        </v-window-item>

                        <!-- Popup menu editing -->
                        <v-window-item :value="1">
                            <v-table density="compact" class="no-row-border no-hover">
                                <tbody>
                                    <tr v-for="(achievement, index) in popupMenu.achievements" :key="index">
                                        <td>{{ goalTexts[achievement.type] }}:</td>
                                        <td class="calendar-popup-input">
                                            <v-text-field
                                                v-model="popupMenu.achievements[index].achievedQuantity"
                                                class="mb-1"
                                                type="number"
                                                hide-details
                                                variant="filled"
                                                density="compact"
                                                rounded
                                                :disabled="popupMenu.saving"
                                                @change="updateAchievement(popupMenu.achievements[index], index, popupMenu.achievements[index].id, popupMenu.achievements[index].achievedQuantity)"
                                            >
                                            </v-text-field>
                                        </td>
                                    </tr>
                                </tbody>
                            </v-table>
                        </v-window-item>
                    </v-window>
                </div>
            </v-menu>

            <!-- Calendar -->
            <div id="calendar-months" class="mt-4">
                <div class="calendar-month" v-for="(month, index) in selectedMonths" :key="index">
                    <div class="calendar-month-title">{{ month.formattedString }}</div>
                    <div class="calendar-month-days">
                        <div
                            :class="{
                                'calendar-day': true,
                                'no-achievement': (selectedGoal == 'reviews_due' && !day.reviewsDue) || (selectedGoal !== 'reviews_due' && (day.achievement == null || day.achievement.achievedQuantity == 0)),
                                'half-achievement': selectedGoal !== 'reviews_due' && day.achievement !== null && day.achievement.achievedQuantity < day.achievement.goalQuantity,
                                'full-achievement': (selectedGoal == 'reviews_due' && day.reviewsDue) || (selectedGoal !== 'reviews_due' && day.achievement !== null && day.achievement.achievedQuantity >= day.achievement.goalQuantity && day.achievement.goalQuantity !== 0),
                            }"
                            transition="fade-transition"
                            @click.stop="openCalendarDayPopup($event, day)"
                            v-for="(day, dayIndex) in month.days"
                            :key="index + '-' + dayIndex"
                        >
                            <div
                                class="calendar-day-background"
                            ></div>
                            <div class="calendar-day-text" v-if="selectedGoal !== 'reviews_due'">{{ day.day }}</div>
                            <div class="calendar-day-text" v-if="selectedGoal == 'reviews_due'">
                                {{ day.reviewsDue ? day.reviewsDue : '-' }}

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </v-card>
    </div>
</template>

<script>
    import {formatNumber} from './../../helper.js';
    import moment from 'moment';
    import { DefaultLocalStorageManager } from './../../services/LocalStorageManagerService';
    export default {
        data: function() {
            return {
                theme: DefaultLocalStorageManager.loadSetting('theme') || 'light',
                calendarData: [],
                reviewsDue: [],
                goalTexts: {
                    'review': 'reviews',
                    'read_words': 'words',
                    'learn_words': 'new words',
                },
                pickerDate: new moment().format('YYYY-MM'),
                pickerDateFormated: '',
                currentMonth: new moment().startOf('month'),
                selectedMonths: [],
                selectedGoal: 'read_words',
                showDatePicker: false,

                popupMenu: {
                    achievements: [],
                    active: false,
                    x: 0,
                    y: 0,
                    day: null,
                    tab: 0,
                    saving: true,
                },
            }
        },
        props: {
        },
        mounted() {
            document.body.addEventListener("click", this.closeCalendarDayPopup);
            // load achievements data
            this.loadCalendarData();
            this.datePickerChanged();
        },
        methods: {
            updateAchievement(achievement, achievementIndex, achievementGoalId, newValue) {
                if (newValue === '' || newValue < 0) {
                    this.popupMenu.achievements[achievementIndex].achievedQuantity = 0;
                    newValue = 0;
                }

                this.popupMenu.saving = true;

                axios.post('/goals/achievement/update', {
                    achievementGoalId: achievementGoalId,
                    achievementType: achievement.type,
                    day: achievement.day,
                    newValue: newValue,
                }).then(() => {
                    this.loadCalendarData();
                    this.$emit('achievement-quantity-change');
                });
            },
            openCalendarDayPopup(event, day) {
                if (event !== null) {
                    var position = event.target.getBoundingClientRect();
                }

                // close calendar if the user clicked on the already selected word
                if (event !== null && this.popupMenu.active &&
                    this.popupMenu.x == position.left - 100 &&
                    this.popupMenu.y == position.bottom + 5) {
                    this.popupMenu.active = false;
                    return;
                }

                // display calendar popup
                if (event !== null) {
                    this.popupMenu.tab = 0;
                }

                this.popupMenu.day = JSON.parse(JSON.stringify(day));
                this.popupMenu.active = false;
                if (event !== null) {
                    this.popupMenu.x = position.left - 100;
                    this.popupMenu.y = position.bottom + 5;
                }

                // get achievements for popup
                this.popupMenu.achievements = [];
                for (var i = 0; i < this.calendarData.length; i++) {
                    if (this.calendarData[i].day == this.popupMenu.day.fullDate) {
                        this.popupMenu.achievements = JSON.parse(JSON.stringify(this.calendarData[i].achievements));
                        break;
                    }
                }

                // add default goals to calendar (reading, reviews, new words)
                var defaultGoalTypes = ['read_words', 'review', 'learn_words'];
                for (var i = 0; i < defaultGoalTypes.length; i++) {
                    if (this.popupMenu.achievements.find(o => o.type == defaultGoalTypes[i]) === undefined) {
                        this.popupMenu.achievements.push({
                            id: -1,
                            day: this.popupMenu.day.fullDate,
                            type: defaultGoalTypes[i],
                            goalQuantity: 0,
                            achievedQuantity: 0
                        });
                    }
                }

                this.popupMenu.achievements.sort((a, b) => defaultGoalTypes.indexOf(a.type) - defaultGoalTypes.indexOf(b.type));


                this.$nextTick(() => {
                    this.popupMenu.active = true;
                });

            },
            closeCalendarDayPopup() {
                this.popupMenu.active = false;
            },
            datePickerChanged() {
                this.pickerDateFormated = new moment(this.pickerDate).format('YYYY, MMMM');
                this.currentMonth = moment(this.pickerDate).startOf('month');
                this.updateCalendar();
                this.showDatePicker = false;
            },
            nextMonth() {
                this.currentMonth.add(1, 'month').startOf('month');
                this.pickerDate = new moment(this.currentMonth).format('YYYY-MM');
                this.pickerDateFormated = moment(this.pickerDate).format('YYYY, MMMM');
                this.updateCalendar();
            },
            previousMonth() {
                this.currentMonth.subtract(1, 'month').startOf('month');
                this.pickerDate = new moment(this.currentMonth).format('YYYY-MM');
                this.pickerDateFormated = moment(this.pickerDate).format('YYYY, MMMM');
                this.updateCalendar();
            },
            loadCalendarData() {
                axios.post('/goals/get-calendar-data').then((response) => {
                    this.calendarData = response.data;
                    this.updateCalendar();
                });
            },
            updateCalendar () {
                this.selectedMonths = null;
                this.selectedMonths = [];

                // loop through days of the month
                var currentDate = new moment(this.currentMonth.format('YYYY-MM-DD')).startOf('month').subtract(4, 'months');
                var lastDay = new moment(this.currentMonth.format('YYYY-MM-DD')).endOf('month');
                while (currentDate.isBefore(lastDay)) {
                    if (!this.selectedMonths.length || this.selectedMonths[this.selectedMonths.length - 1].formattedString !== currentDate.format('YYYY, MMMM')) {
                        this.selectedMonths.push({
                            formattedString: currentDate.format('YYYY, MMMM'),
                            days: [],
                        });
                    }

                    var day = {
                        day: currentDate.format('D'),
                        dayWithLeadingZeros: currentDate.format('DD'),
                        fullDate: currentDate.format('YYYY-MM-DD'),
                        achievement: null,
                        reviewsDue: 0,
                    };

                    // add achievements for the day
                    for (var i = 0; i < this.calendarData.length; i++) {

                        if (this.calendarData[i].day !== currentDate.format('YYYY-MM-DD')) {
                            continue;
                        }

                        day.reviewsDue = this.calendarData[i].reviewsDue;
                        for (var j = 0; j < this.calendarData[i].achievements.length; j++){
                            if (this.selectedGoal == this.calendarData[i].achievements[j].type) {
                                day.achievement = {
                                    name: this.calendarData[i].achievements[j].name,
                                    type: this.calendarData[i].achievements[j].type,
                                    achievedQuantity: this.calendarData[i].achievements[j].achievedQuantity,
                                    goalQuantity: this.calendarData[i].achievements[j].goalQuantity,
                                }
                            }
                        }
                    }

                    this.selectedMonths[this.selectedMonths.length - 1].days.push(day);
                    currentDate.add(1, 'days');
                }

                this.selectedMonths.reverse();
                this.popupMenu.saving = false;

                if (this.popupMenu.active) {
                    this.openCalendarDayPopup(null, this.popupMenu.day);
                }
            },
            formatNumber: formatNumber
        }
    }
</script>
