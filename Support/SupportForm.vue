<template>
    <div>
        <animated-card class="pb-6" style="padding: 0px;">
            <card-title>Contact us</card-title>
                <div class="px-5 pb-6">
                <div class="mb-6">
                    <slot></slot>
                </div>
                <form @submit="submitForm" v-if="!showSuccess">
                    <div class="flex flex-row mb-8 mt-6" v-if="!isSupportQuery">
                        <basic-input 
                            :error="nameError"
                            :tabindex="1"
                            type="text"
                            placeholder="Name"
                            :required="true"
                            v-model="name"
                        ></basic-input>
                        <basic-input 
                            class="ml-6 w-full"
                            :error="emailError"
                            :tabindex="2"
                            type="email"
                            :required="true"
                            placeholder="Email"
                            v-model="email"
                        ></basic-input>
                    </div>
                    <basic-textarea
                        :error="messageError" 
                        placeholder="Your query"
                        :required="true"
                        v-model="message"
                        classes="h-32 mb-4"
                        :focus="false"
                        :tabindex="3"
                    ></basic-textarea>
                    <div class="flex flex-row w-full justify-end">
                        <s-button :loading="loading" class="text-white bg-blue-400">
                            Submit
                        </s-button>
                    </div>
                </form>
                <info-box v-if="showSuccess">
                    <p>Great your query is with us, we'll get back to you soon!</p>
                </info-box>
            </div>
        </animated-card>
    </div>
</template>

<script>

import BasicInput from '../../components/medium/BasicInput'
import BasicTextarea from '../../components/medium/BasicTextarea'


export default {
    components: {
        BasicInput,
        BasicTextarea
    },
    props: ['isSupportQuery'],
    data(){
        return {
            name: '',
            nameError: '',
            email: '',
            emailError: '',
            message: '',
            messageError: '',
            showSuccess: false,
            loading: false
        }
    },
    methods: {
        submitForm(e){
            e.preventDefault();
            this.loading = true
            const url = this.isSupportQuery ? '/api/support' : '/api/support/sales'
            this.sendQuery(url)
        },
        sendQuery(url){
            axios.post(url, {
                name: this.name,
                email: this.email,
                message: this.message
            }).then(res => {
                this.showSuccess = true
                this.resetForm()
                this.loading = false
            }).catch(e => {
                this.handleErrors(e.response.data.errors)
                this.loading = false
            })
        },
        handleErrors(errors){
            for (let [key, value] of Object.entries(errors)) {
                this[`${key}Error`] = value[0];
            }
        },
        resetForm(){
            this.name = ''
            this.email = ''
            this.message = ''
        }
    }
}
</script>