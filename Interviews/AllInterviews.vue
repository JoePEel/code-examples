<template>
    <div>
        <page-heading
            title="Interviews"
            class="mb-8"
        ></page-heading>
        <narrow-container>
            <interview-page-list
                :interviews="upcomingInterviews"
                :showFeedbackOption="false"
                :showConfirmedOption="true"
                class="mb-8"
                @updated="getInterviews"
                name="upcoming"
                message="Not much going on here... best be booking some interviews!"
            >
                Upcoming
            </interview-page-list>
            <interview-page-list
                :interviews="needsFeedback"
                :showFeedbackOption="true"
                class="mb-8"
                @updated="getInterviews"
                message="Great news, you're all up to date!"
            >
                Awaiting Feedback
            </interview-page-list>
            <interview-page-list
                :interviews="pastInterviews"
                :showFeedbackOption="true"
                class="mb-8"
                @updated="getInterviews"
                message="Bit quiet isn't it?"
                name="past"
            >
                Last 30 Days
            </interview-page-list>
        </narrow-container>
    </div>
</template>

<script>
import Toolbar from '../../components/medium/Toolbar'
import PageHeading from '../../components/small/PageHeading'
import InterviewPageList from '../../components/large/InterviewPageList'

export default {
    data(){
        return {
            upcomingInterviews: [],
            needsFeedback: [],
            pastInterviews: []
        }
    },
    components: {
        Toolbar,
        PageHeading,
        InterviewPageList
    },
    mounted(){
        this.getInterviews();
    },
    methods: {
        getInterviews(){
            axios.get('/api/interviews').then(res => {
                const data = res.data.data
                this.upcomingInterviews = data.current,
                this.pastInterviews = data.past
                this.needsFeedback = data.needsFeedback
            }).catch(e => console.log(e))
        }
    }
}
</script>


<style>

</style>
