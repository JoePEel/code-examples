<template>
    <div>
        <toolbar
            @search="handleSearch"
            :hideFilterButton="true"
        >
            <template v-slot:title>
                <span class="font-semibold">Onboarding</span> 
            </template>
            <template v-slot:vacancy-filters>
                <button
                    class="cursor-pointer rounded-l bg-white py-0 px-2 focus:outline-none shadow hover:shadow-lg text-sm h-8"
                    @click="showIncomplete = !showIncomplete, handleFilter()"
                    :class="showIncomplete ? 'bg-gray-500 text-white' : ''"
                >
                    Incomplete
                </button>
                <button
                    class="cursor-pointer rounded-r  bg-white py-0 px-2 focus:outline-none shadow hover:shadow-lg text-sm h-8"
                    @click="showOnboarded = !showOnboarded, handleFilter()"
                    :class="showOnboarded ? 'bg-blue-400 text-white' : ''"
                >
                    Onboarded
                </button>                 
            </template>
        </toolbar>
        <narrow-container class="my-8">
            <animated-card style="padding: 0px">
                <div v-if="offers" class="w-full">
                    <div
                        v-for="(offer, i) in offers"
                        :key="i"
                    >
                        <onboarding-row :offer="offer" />
                    </div>
                </div>
                <div v-if="!offers.length" class="py-5 px-5">
                    <info-box 
                        class="flex items-center my-5 px-5"
                        v-if="loaded"
                    >
                        <p class="mx-auto" v-if="!offersData.length">There are no candidates added to onboarding yet!</p>
                        <p class="mx-auto" v-else>No candidates met your search</p>
                    </info-box>
                </div>
            </animated-card>
        </narrow-container>
    </div>
</template>

<script>
import Toolbar from '../../components/medium/Toolbar'
import ActionButton from '../../components/small/ActionButton'
import NarrowContainer from '../../components/small/NarrowContainer'
import OnboardingRow from '../../components/medium/OnboardingRow'

export default {
    name: 'allOnboarding',
    data(){
        return {
            offers: {},
            offersData: {},
            offersFilteredData: {},
            loaded: false,
            search: '',
            showIncomplete: true,
            showOnboarded: false
        }
    },
    components: {
        Toolbar,
        ActionButton,
        NarrowContainer,
        OnboardingRow
    },
    activated(){
        this.getOnboarding()
    },
    mounted(){
        this.getOnboarding()
    },
    methods: {
        getOnboarding(){
            axios.get('/api/onboarding/candidates').then(resp => {
                const data = resp.data.data.map(offer=> {
                    if(offer.completed_onboarding_count >= offer.onboarding_count && offer.start_date){
                        offer.completed = true
                    }
                    return offer
                })
                this.offers = data
                this.offersData = data
                this.loaded = true
                this.handleFilter()
            })
        },
        handleSearch(search){
            this.search = search
            this.offers = this.offersFilteredData.filter(offer => {
                const candidate = offer.candidate
                const lowerCaseName = candidate.first_name.toLowerCase() + candidate.last_name.toLowerCase();
                const lowerCaseSearch = search.toLowerCase();
                return lowerCaseName.includes(lowerCaseSearch)
            })
        },
        handleFilter(){
            this.offersFilteredData = this.offersData.filter(offer => {
                if(offer.completed && this.showOnboarded){
                    return offer
                }
                if(!offer.completed && this.showIncomplete){
                    return offer
                }
            })
            this.handleSearch(this.search)
        }
    }
}
</script>


<style>

</style>
