<template>
    <v-app id="inspire">
        <v-content>
            <v-container class="fill-height" fluid>
                <v-row align="center" justify="center">
                    <v-col cols="12" sm="8" md="4">
                        <v-card class="elevation-12">
                            <v-toolbar color="error" dark flat>
                                <v-toolbar-title>Login form</v-toolbar-title>
                                <v-spacer />
                            </v-toolbar>

                            <v-card-text>
                                <v-progress-linear
                                    :active="loading"
                                    :indeterminate="loading"
                                    absolute
                                    top
                                    color="white accent-4">
                                </v-progress-linear>

                                <v-form
                                    ref="form"
                                    v-model="valid"
                                >
                                    <v-text-field
                                        label="Login"
                                        name="login"
                                        :rules="emailRules"
                                        color="error"
                                        prepend-icon="mdi-account-circle-outline"
                                        type="email"
                                        v-model="email"
                                        required
                                    />

                                    <v-text-field
                                        id="password"
                                        label="Password"
                                        name="password"
                                        prepend-icon="mdi-lock"
                                        type="password"
                                        color="error"
                                        v-model="password"
                                        :rules="passwordRules"
                                        required
                                    />
                                </v-form>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer />
                                <v-btn color="error" @click="login" :disabled="!valid" block> Login</v-btn>
                            </v-card-actions>
                        </v-card>
                        <v-snackbar
                            v-model="snackbar"
                            top>
                            {{ text }}
                            <v-btn
                                color="pink"
                                text
                                @click="snackbar = false"
                            >Close
                            </v-btn>
                        </v-snackbar>
                    </v-col>
                </v-row>
            </v-container>
        </v-content>
    </v-app>
</template>

<script>
    export default {
        data(){
            return {
                email   : '',
                password: '',
                loading : false,
                snackbar: false,
                text    : '',
                valid   : true,
                emailRules:[
                    v => !!v || 'E-mail is required',
                    v => /.+@.+\..+/.test(v) || 'E-mail must be valid',
                ],
                passwordRules:[
                    v=>!!v || "Password is required"
                ]
            }
        },
        created(){
            this.$vuetify.theme.dark = true;
        },
        methods:{
            login(){
                // Add a request intercept or
                axios.interceptors.request.use((config)=> {
                    //Do something before request is sent
                    this.loading=true;
                    console.log("config");
                    console.log(config);
                    return config;
                }, (error)=> {
                    this.loading=false;
                    return Promise.reject(error);
                });

                // Add a response interceptor
                axios.interceptors.response.use((response)=> {
                    this.loading=false;
                    return response;
                }, (error)=> {
                    this.loading=false;
                    return Promise.reject(error);
                });


                axios.post('/api/login',{'email':this.email,'password':this.password})
                .then((response)=>{
                    console.log(response);
                    if(response.data.isAdmin){
                        //set token in localstorage
                        localStorage.setItem('token',response.data.token);
                        localStorage.setItem('loggedIn',true);
                         //then redirect the user to the admin route
                        this.$router.push('/admin')
                        .then(res=>console.log("LoggedIn Successfully"))
                        .catch(err=>console.log(err));
                    }
                    else{
                        this.text     = "You need to be LoggedIn as an administrator";
                        this.snackbar = true;
                    }

                }).catch(err=>{
                    this.text     = err.response.data.status;
                    this.snackbar = true;
                    this.loading  = false;
                });

            }
        },

    };
</script>

<style lang="stylus" scoped></style>
