<template>
    <div>
        <animated-card style="padding: 0px">
            <card-title class="pt-3 px-3">
                <div class="flex flex-row justify-between items-center">
                    <slot></slot>
                    <span>{{interviews.length}}</span>
                </div>
            </card-title>
            <div v-if="interviews.length">
                <div
                    v-for="(interview, i) in interviews"
                    :key="i"
                    v-if="i <= resultLimit"
                    class="flex flex-col sm:flex-row justify-between hover:bg-gray-100 p-3 border-b"
                    :class="[showFeedbackOption && interview.feedback_given ? 'text-gray-700 bg-gray-100' : '',
                        !showFeedbackOption && interview.confirmed ? 'text-gray-700 bg-gray-100' : ''
                    ]"
                >
                <div class="flex flex-col">
                    <div class="flex flex-row font-semibold flex-wrap">
                        <router-link :to="`/candidates/${interview.hiring_process.candidate ? interview.hiring_process.candidate.id : ''}/activity`">
                            <p 
                                class="cursor-pointer hover:underline"
                                v-if="interview.hiring_process.candidate"
                            >
                                {{interview.hiring_process.candidate.first_name}} {{interview.hiring_process.candidate.last_name}}
                            </p>
                        </router-link>
                        <p class="px-1 font-bold"> -</p>
                        <router-link :to="`/vacancies/${interview.hiring_process.vacancy.id}/activity`">
                            <p 
                                class="cursor-pointer hover:underline"
                            >
                                {{interview.hiring_process.vacancy.title}}
                            </p>
                        </router-link>
                    </div>
                    <p class="text-sm italic text-gray-700">{{formatDate(interview.date)}}</p>
                </div>
                <div class="flex flex-row items-center justify-between">
                    <div 
                        v-if="showFeedbackOption" 
                        class="flex flex-row items-center mr-1"
                    >
                        <span 
                            :class="interview.feedback_given == true ? 'border-blue-500 text-blue-500' : 'border-gray-700 text-gray-700'"
                            class="rounded border text-sm mr-2 px-2 sm:ml-2 text-center"
                            style="min-width: 140px;"
                        > 
                            <span v-if="interview.feedback_given">Feedback Given</span>
                            <span v-else>Awaiting Feedback</span>
                        </span>
                    </div>
                    <div 
                        v-if="showConfirmedOption" 
                        class="flex flex-row items-center mr-1"
                    >
                        <span 
                            :class="interview.confirmed == true ? 'border-blue-500 text-blue-500' : 'border-gray-700 text-gray-700'"
                            class="rounded border text-sm mr-2 px-2"
                        > 
                            <span v-if="interview.confirmed">Confirmed</span>
                            <span v-else>Not Confirmed</span>
                        </span>
                    </div>
                    <icon 
                        class="bottom-0 mr-1"
                        size="24"
                        :button="true"
                        @clicked="launchInterviewModal(interview)"
                        key="view"
                    >
                        remove_red_eye
                    </icon>
                </div>

                </div>
            </div>
            <div v-else class="text-center px-5 pb-5 pt-1">
                <info-box >{{message}}</info-box>
            </div>
            <div
                v-if="resultLimit <= interviews.length - 2"
                class="flex items-center mt-6"
            >
                <action-button 
                    @clicked="resultLimit+= 10"
                    class="mx-auto mb-6"
                >
                    Show More
                </action-button>
            </div>
        </animated-card>

        <interview-modal 
            :candidate="selectedCandidate"
            :vacancy="selectedVacancy"
            :interview="selectedInterview"
            @close="handleCloseModal"
            :name="name"
        />
    </div>
</template>

<script>
import moment from 'moment'
import ActionButton from '../small/ActionButton'
import InterviewModal from '../large/InterviewModal'

export default {
    components: {
        ActionButton,
        InterviewModal
    },
    props: ['interviews', 'showFeedbackOption', 'message', 'showConfirmedOption', 'name'],
    data(){
        return {
            resultLimit: 10,
            selectedCandidate: {},
            selectedVacancy: {},
            selectedInterview: {},
        }
    },
    methods: {
        toggleFeedback(interview){
            axios.patch(`/api/interview/${interview.id}`, {
                feedback_given: interview.feedback_given ? false : true
            }).then(res => {
                this.$emit('updated')
            }).catch(e => console.log(e))
        },
        toggleConfirmed(interview){
            axios.patch(`/api/interview/${interview.id}`, {
                confirmed: interview.confirmed ? false : true
            }).then(res => {
                this.$emit('updated')
            }).catch(e => console.log(e))
        },
        formatDate(date){
            return this.$root.convertToLocal(date).format('ddd Do MMM YY - hh:mma')
        },
        launchInterviewModal(interview){
            this.selectedInterview = interview
            this.selectedVacancy = interview.hiring_process.vacancy
            this.selectedCandidate = interview.hiring_process.candidate
            this.$modal.show(this.name)
        }, 
        handleCloseModal(){
            this.selectedInterview = {}
            this.selectedVacancy = {}
            this.selectedCandidate = {}
            this.$modal.hide(this.name)
            this.$emit('updated')
        }
    }
}
</script>