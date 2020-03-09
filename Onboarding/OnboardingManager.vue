<template>
    <card  class="p-5">
        <div class="flex flex-col sm:flex-row sm:items-end py-4">
            <basic-input 
                    class="mr-4 mb-4 sm:mb-0 w-full sm:w-1/2"
                    :error="nameError"
                    :tabindex="1"
                    type="text"
                    label="To Do"
                    v-model="name"
            ></basic-input>
            <div class="mb-1">
                <s-button
                    @clicked="add()"
                    v-if="!edditing"
                    :blue="true"
                    :tabindex="2"
                >
                    Add
                </s-button>
                <div class="flex flex-row" v-else>
                    <s-button
                        @clicked="update()"
                        :blue="true"
                        class="mr-3"
                        :tabindex="3"
                    >
                        Update
                    </s-button>
                    <s-button
                        @clicked="deleteItem()"
                        class="mr-3"
                        :red="true"
                        :tabindex="4"
                    >
                        Delete
                    </s-button>
                    <s-button
                        @clicked="handleCancelled()"
                        :tabindex="4"
                    >
                        Cancel
                    </s-button>
                </div>
            </div>
        </div>
    </card>
</template>


<script>

import BasicInput from '../medium/BasicInput'

export default {
    data(){
        return {
            name: '',
            nameError: ''
        }
    },
    props: ['candidate', 'onboarding', 'offer', 'edditing'],
    components: {
        BasicInput
    },
    watch: {
        onboarding(){
            if(this.onboarding){
                this.nameError = ''
                this.name = this.onboarding.name
            } else {
                this.name = ''
            }
        }
    },
    mounted(){
        if(this.onboarding){
            this.nameError = ''
            this.name = this.onboarding.name
        } else {
            this.name = ''
        }
    },
    methods: {
        add(){
            axios.post(`/api/onboarding/${this.offer.id}`, {
                name: this.name
            }).then(res => {
                this.reset()
            }).catch(e => this.handleErrors(e.response.data.errors))
        },
        update(){
            axios.patch(`/api/onboarding/${this.onboarding.id}`, {
                name: this.name,
                completed: this.onboarding.completed
            }).then(res => {
                this.reset()
            }).catch(e => this.handleErrors(e.response.data.errors))
        },
        deleteItem(){
            axios.delete(`/api/onboarding/${this.onboarding.id}`).then(res => {
                this.reset()
            }).catch(e => console.log(e))
        },
        handleCancelled(){
            this.nameError = ''
            this.$emit('cancelled')
        },
        reset(){
            this.name = ''
            this.nameError = ''
            this.$emit('updated')
        },
        handleErrors(errors){
            for (let [key, value] of Object.entries(errors)) {
                this[`${key}Error`] = value[0];
            }
        }
    },

}
</script>