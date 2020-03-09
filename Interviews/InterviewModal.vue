<template>
    <modal 
        :name="name" 
        classes="bg-white p-3 rounded" 
        class="overflow-y-scroll"
        :width="500" 
        height="auto" 
        :adaptive="true" 
        :scrollable="true"
    >
        <icon class="absolute" style="top: 8px; right: 8px;" :size="30" @clicked="$emit('close')">close</icon>
        <h2 class="text-gray-700">Interview</h2>
        <h3 class="text-lg font-bold mt-0 mb-2">
            {{candidate.first_name}} {{candidate.last_name}} <span class="text-base font-normal">for</span> {{vacancy.title}}
        </h3>
        <div class="mb-8">
            <button
                class="cursor-pointer rounded-l bg-white py-0 px-2 focus:outline-none shadow hover:shadow-lg text-sm h-8 -mr-1"
                @click="activeTab = 'booking'"
                :class="activeTab == 'booking' ? 'bg-blue-500 text-white' : ''"
            >
                Booking
            </button>
            <button
                class="cursor-pointer rounded-r  bg-white py-0 px-2 focus:outline-none shadow hover:shadow-lg text-sm h-8"
                @click="activeTab = 'feedback'"
                :class="activeTab == 'feedback' ? 'bg-blue-500 text-white' : ''"
            >
                Feedback
            </button>  
        </div>
        <div style="max-width: 350px;" class="mx-auto" v-if="activeTab == 'booking'">
            <div class="mb-4">
                <date-time-picker :date-error="dateError" :existing-date="this.$root.convertToLocal(interview.date)" @updated="updateDate" />
            </div>
            <basic-select
                label="Type"
                v-model="type"
                :options="interviewTypes"
            >

            </basic-select>
            <div>
                <div class="flex flex-row flex-wrap my-4">
                    <div
                        v-for="(interviewer, i) in interviewers"
                        :key="i"
                        class="flex relative flex-row inline-block border border-gray-700 mr-2 mt-3 text-gray-700 pl-2 pr-1 py-1 rounded-lg"
                    >   
                        {{interviewer}}
                        <i 
                            class="material-icons  text-gray-700 cursor-pointer rounded-full p-0 ml-2"
                            @click="removeInterviewer(interviewer)"
                        >
                            close
                        </i>
                    </div>
                </div>
                <div class="flex flex-row items-center mb-6" v-if="interviewerOptions.length">
                    <basic-select
                        :options="interviewerOptions"
                        v-model="selectedInterviewer"
                        label="Interviewers"
                    />
                    <s-button @clicked="addInterviewer()"
                        class="ml-6 mt-6"
                        :white="true"
                    >
                        Add
                    </s-button>
                </div>
            </div>
            <confirm-switch
                class="mt-8 mb-6"
                :active="confirmed"
                @clicked="confirmed = !confirmed"
            >
                <span v-if="confirmed">Confirmed</span>
                <span v-else>Not Confirmed</span>
            </confirm-switch>
        </div>
        
        <div v-else style="max-width: 350px;" class="mx-auto">
            <div>
                <span v-if="interview.type">{{interview.type}} on </span>
                <p class="text-xl italic">{{formatDate(this.interview.date)}}</p>
                <span v-if="interviewers.length"> with </span>
                <span 
                    v-for="(interviewer, i) in interviewers"
                    :key="i"
                >
                    {{ interviewer }}<span>, </span>
                </span>
            </div>
            <div 
                class="flex flex-row mr-3 mb-8 mt-6 text-xl"
            >
                <button
                    @click="feedback_status = 'REJECTED'"
                    class="rounded-l bg-white py-0 px-2 focus:outline-none shadow hover:shadow-lg text-sm h-8"
                    :class="feedback_status == 'REJECTED' ? 'bg-red-500 text-red-100' : ''"
                >
                    Rejected
                </button>
                <button
                    @click="feedback_status = 'PENDING'"
                    class="bg-white py-0 px-2 focus:outline-none shadow hover:shadow-lg text-sm h-8"
                    :class="feedback_status == 'PENDING' ? 'bg-gray-500 text-gray-100' : ''"
                >
                    Pending
                </button>                   
                <button
                    @click="feedback_status = 'SUCCESS'"
                    class="rounded-r bg-white py-0 px-2 focus:outline-none shadow hover:shadow-lg text-sm h-8"
                    :class="feedback_status == 'SUCCESS' ? 'bg-green-500 text-green-100' : ''"
                >
                    Success
                </button>
            </div>
            <basic-textarea
                class="mb-5 mt-5 w-full"
                placeholder="Notes"
                v-model="feedback"
            >
            </basic-textarea>
            <confirm-switch
                :active="feedback_given"
                @clicked="feedback_given = !feedback_given"
                v-if="feedback_status != 'PENDING'"
                class="mb-4"
            >
                Feedback Given
            </confirm-switch>
        </div>

        <div class="p-4">
            <div v-if="!showDeleteConfirmation">
                <div v-if="(!interview || !interview.id)" class="flex flex-row justify-end">
                    <s-button
                        class="flex flex-row items-center"
                        :tabindex="7"
                        :blue="true"
                        @clicked="bookInterview()"
                    >
                        <i 
                            class="material-icons"
                            style="font-size: 22px; top: 6px;"
                        >
                            save
                        </i>
                        <span class="ml-2">Book</span>
                    </s-button>
                </div>
                <div v-else class="flex items-center flex-row justify-between">
                    <p
                        @click="showDeleteConfirmation = true"
                        class="text-xs hover:underline cursor-pointer text-red-500"  
                    >
                        Delete
                    </p>
                    <div>
                        <s-button
                            class="flex flex-row items-center"
                            :tabindex="7"
                            :blue="true"
                            @clicked="updateInterview()"
                        >
                            <i 
                                class="material-icons"
                                style="font-size: 22px; top: 6px;"
                            >
                                save
                            </i>
                            <span class="ml-2">Update</span>
                        </s-button>
                    </div>
                </div>
            </div>
            <div v-else class="text-center">
                <p>Are you sure you want to delete this interview?</p>
                <p>This action cannot be undone</p>
                <div class="flex flex-row justify-center mt-3">
                    <small-action-button
                        @clicked="showDeleteConfirmation = false"
                        class="mr-3"                
                    >
                        Cancel
                    </small-action-button>
                    <small-action-button
                        @clicked="deleteInterview()"
                        class="ml-3"
                        :red="true"                
                    >
                        Delete
                    </small-action-button>
                </div>
            </div>
        </div>
    </modal>
</template>

<script>
import SmallActionButton from '../small/SmallActionButton'
import moment from 'moment'
import BasicInput from '../../components/medium/BasicInput'
import BasicSelect from '../../components/medium/BasicSelect'
import BasicTextarea from '../../components/medium/BasicTextarea'
import ConfirmationModal from '../medium/ConfirmationModal'
import ConfirmSwitch from '../small/ConfirmSwitch'
import { Datetime } from 'vue-datetime';
import DateTimePicker from '../../components/medium/DateTimePicker'

export default {
    components: {
        SmallActionButton,
        BasicInput,
        BasicSelect,
        ConfirmationModal,
        ConfirmSwitch,
        Datetime,
        DateTimePicker,
        BasicTextarea
    },
    props: ['vacancy', 'candidate', 'name', 'interview'],
    data(){
        return {
            date: '',
            dateError: false,
            type: '',
            location: '',
            confirmed: false,
            feedback_given: false,
            feedback_status: 'PENDING',
            feedback: '',
            interviewers: [],
            interviewerOptions: [],
            allInterviewers: [],
            selectedInterviewer: '',
            showDeleteConfirmation: false,
            interviewTypes: [
                {text: '', value: ''},
                {text: 'In Person', value: 'In Person'},
                {text: 'Phone', value: 'Phone'},
                {text: 'Video', value: 'Video'},
                {text: 'Assessment', value: 'Assessment'},
                {text: 'Skype', value: 'Skype'},
                {text: 'Other', value: 'Other'}
            ],
            activeTab: 'booking'
        }
    },
    mounted(){
        this.getInterviewers()
        this.setUpInterview()
        this.showDeleteConfirmation = false
    },
    watch: {
        interview(){
            this.setUpInterview()
        },
        interviewers(){
            this.getInterviewerOptions()
        }
    },
    methods: {
        setUpInterview(){
                if(this.interview.id) {
                    this.date = this.$root.convertToLocal(this.interview.date)
                    this.type = this.interview.type
                    this.confirmed = this.interview.confirmed
                    this.location = this.interview.location
                    this.interviewers = this.interview.interviewers
                    this.feedback_given = this.interview.feedback_given
                    this.feedback_status = this.interview.feedback_status
                    this.feedback = this.interview.feedback
                if(this.$root.convertToUtc(this.interview.date) < moment.utc()){
                    this.activeTab = 'feedback'
                } else {
                    this.activeTab = 'booking'
                }
            }
            this.showDeleteConfirmation = false
        },
        bookInterview(){
            const data = this.getData()
            if(!this.date){
                this.dateError = true
                return
            }
            this.dateError = false
            axios.post(`/api/interview/${this.vacancy.id}/${this.candidate.id}`, {
                ...data
            }).then(res => {
                this.resetForm();
                this.$emit('close')
            })
        },
        updateInterview(){
            const data = this.getData()
            axios.patch(`/api/interview/${this.interview.id}`, {
                ...data
            }).then(res => {
                this.$emit('close')
            })
        },
        getData(){
            return {
                date: this.date ? this.$root.convertToUtc(this.date) : null,
                type: this.type,
                location: this.location,
                interviewers: this.interviewers,
                confirmed: this.confirmed,
                feedback_given: this.feedback_given,
                feedback_status: this.feedback_status,
                feedback: this.feedback
            }
        },
        getInterviewers(){
            axios.get('/api/interviewers').then(res => {
                this.allInterviewers = res.data.data
                this.getInterviewerOptions()
            })
        },
        getInterviewerOptions(){
            let newOptions = [];
            this.allInterviewers.map(interviewer => {
                let alreadyAdded = this.interviewers.find(current => current === interviewer);
                if(!alreadyAdded){
                    newOptions.push({
                        value: interviewer,
                        text: interviewer              
                    })
                }
            })
            this.interviewerOptions = newOptions
            if(this.interviewerOptions.length){
                this.selectedInterviewer = this.interviewerOptions[0].value
            }
        },
        addInterviewer(){
            this.interviewers.push(this.selectedInterviewer)
            this.selectedInterviewer = ''
        },
        removeInterviewer(interviewer){
            this.interviewers.splice(this.interviewers.indexOf(interviewer), 1)
        },
        deleteInterview(){
            axios.delete(`/api/interview/${this.interview.id}`).then(res => {
                this.$emit('close')
            })
        },
        resetForm(){
            this.date = ''
            this.type = ''
            this.location = ''
            this.confirmed = false
            this.interviewers = []
            this.feedback_given = false
            this.feedback_status = 'PENDING'
            this.feedback  = ''
        },
        updateDate(date){
            this.date = date
        },
        formatDate(date){
            return this.$root.convertToLocal(date).format('dddd Do MMM YY - hh:mma')
        }
    }
}
</script>

<style>

.v--modal-box{
    height: 100% !important;
    bottom: 100px !important;
    top: 100px !important;
    overflow: inherit !important;
}

.date-time-picker input{
    border: 2px solid #CBD5E0 !important;
}

.date-time-picker input:focus {
    border: 2px solid #718096 !important;
}

</style>
