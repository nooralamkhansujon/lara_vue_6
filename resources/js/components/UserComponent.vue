<template>
    <v-data-table
        item-key="name"
        class="elevation-1"
        color="error"
        :loading="loading"
        loading-text="Loading... Please wait"
        :headers="headers"
        :items="users.data"
        :items-per-page="5"
        show-select
        :server-items-length="users.total"
        :options.sync="options"
        @pagination="paginate"
        @input="selectAll"
        :footer-props="{
            itemsPerPageOptions: [5, 10, 15],
            itemsPerPageText: 'Users Per Page',
            showCurrentPage :true,
            showFirstLastPage:true
        }"
    >
        <template v-slot:top>

            <v-toolbar flat color="dark">
                <v-toolbar-title>
                    User Management System
                </v-toolbar-title>
                <v-divider class="mx-4" inset vertical></v-divider>
                <v-spacer></v-spacer>

                <v-dialog v-model="dialog" max-width="500px">

                    <template v-slot:activator="{ on }">
                        <v-btn color="error" dark class="mb-2" v-on="on">
                            Add New User
                        </v-btn>
                         <v-btn color="error" @click="deleteAll" dark class="mb-2 mr-2">
                            Delete
                    </v-btn>

                    </template>
                    <v-card>
                        <v-card-title>
                            <span class="headline">{{ formTitle }}</span>
                        </v-card-title>
                    <v-form
                        @submit.stop.prevent="save"
                    >
                        <v-card-text>
                            <v-container>
                                <v-row>
                                    <v-col cols="12" sm="12">
                                        <v-text-field
                                            color="error"
                                            v-model="editedItem.name"
                                            label="Name"
                                            :rules="[rules.required,rules.min]"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                                <v-row v-if="editedIndex == -1">
                                    <v-col cols="12" sm="12">
                                        <v-text-field
                                            color="error"
                                            v-model="editedItem.password"
                                            label="Password"
                                            type="password"
                                            :rules="[rules.required]"
                                        ></v-text-field>
                                    </v-col>

                                     <v-col cols="12" sm="12">
                                        <v-text-field
                                            color="error"
                                            type="password"
                                            v-model="editedItem.rpassword"
                                            label="ReType Password"
                                            :rules="[rules.required,passwordMatch]"
                                        ></v-text-field>
                                    </v-col>
                                     <v-col cols="12" sm="12">
                                        <v-text-field
                                            color="error"
                                            :success-messages="success"
                                            :error-messages="error"
                                            @blur="checkEmail"
                                            v-model="editedItem.email"
                                            label="Email"
                                            :rules="[rules.required,rules.emailValid]"
                                        ></v-text-field>
                                    </v-col>
                                </v-row>
                                <v-row>
                                     <v-col cols="12" sm="12">
                                        <v-select
                                            :items="roles"
                                            v-model="editedItem.role"
                                            color="error"
                                            label="Select Role"
                                            :rules="[rules.required]"
                                       ></v-select>
                                    </v-col>
                                </v-row>
                            </v-container>
                        </v-card-text>
                 </v-form >
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="error darken-1" text @click="close"
                            >Cancel</v-btn>
                            <v-btn color="error darken-1" text @click="save"
                            >Save</v-btn>
                        </v-card-actions>

                    </v-card>
                </v-dialog>
            </v-toolbar>
            <v-row>
                <v-col cols="12" mx=4 sm="12">
                    <v-text-field @input="searchIt" color="error" label="Search..."></v-text-field>
               </v-col>
            </v-row>
        </template>
        <template v-slot:item.role="{ item }">
            <v-edit-dialog
              large
              block
              persistent
              :return-value-sync="item.role"
              @save="updateRole(item)"
             >
                {{item.role}}
                <template v-slot:input>
                    <h2>Change Role</h2>
                </template>
                <template v-slot:input>
                    <v-select :rules="[rules.required]" :items="roles" v-model="item.role" color="error"
                    label="Select Role"></v-select>
               </template>
            </v-edit-dialog>

        </template>
        <template v-slot:item.photo="{ item }">
        <v-edit-dialog>
            <v-list-item-avatar>
                <v-img
                    :src="item.photo"
                    :lazy-src="item.photo"
                    aspect-ratio="1"
                    class="grey lighten-2 rounded"
                    max-width="50"
                    max-height="50"
                ></v-img>
            </v-list-item-avatar>
            <template v-slot:input>
                <v-file-input
                    label="Select File"
                    v-model="editedItem.photo"
                    placeholder="Upload Avater"
                    accept="image/jpg, image/png,image/bmp,image/jpeg"
                    @change="uploadPhoto(item)"
                    />
            </template>
        </v-edit-dialog>
        </template>

        <template v-slot:item.actions="{ item }">
            <v-icon small class="mr-2" @click="editItem(item)">
                mdi-pencil
            </v-icon>
            <v-icon small @click="deleteItem(item)">
                mdi-delete
            </v-icon>

            <v-snackbar v-model="snackbar" top>
                {{ text }}
                <v-btn color="error" text @click="snackbar = false">
                    Close
                </v-btn>
            </v-snackbar>
        </template>
        <template v-slot:no-data>
            <v-btn color="error" @click="initialize">Reset</v-btn>
        </template>
    </v-data-table>
</template>

<script>
export default {
    data(){
        return {
            valid:true,
            dialog: false,
            loading: false,
            snackbar: false,
            error:'',
            success:"",
            selected:[],
            roles:[],
            users: [],
            options:{
                sortBy:['name'],
                sortDesc:[true]
            },
            rules:{
                required:v=> !!v || "This Field is required",
                min:v => v.length >= 5 || "Minimum 5 Characters Required",
                emailValid:v => /.+@.+\..+/.test(v) || 'E-mail must be valid',

            },
            text: "this is default value",
            headers: [
                {
                    text: "#",align: "start",sortable: false,value: "id"
                },
                { text: "Name", value:"name" },
                { text: "Email", value:"email" },
                { text: "Role", value:"role" },
                { text: "Photo", value:"photo",sortable: false },
                { text: "Created At", value: "created_at" },
                { text: "Updated At", value: "updated_at" },
                { text: "Actions", value: "actions" }
            ],
            editedIndex: -1,
            editedItem: {
                id: "",
                name: "",
                email:'',
                password:"",
                rpassword:"",
                role:"",
                created_at: "",
                updated_at: "",
                photo:null
            },
            defaultItem: {
                id: "",
                name: "",
                email:"",
                role:"",
                created_at: "",
                updated_at: ""
            }
      };
    },

    computed: {
        formTitle() {
            return this.editedIndex === -1 ? "New User" : "Edit User";
        },
        passwordMatch(){
             return this.editedItem.password  != this.editedItem.rpassword ? "Password Does not match" : "";
        },
    },

    watch: {
        dialog(val) {
            val || this.close();
        }
    },
    created() {
        this.initialize();
        this.$vuetify.theme.dark = true;
    },

    methods: {
        uploadPhoto(item){
            if(this.editedItem.photo != null)
            {
                const index = this.users.data.indexOf(item);
                let formData = new FormData();
                formData.append('photo',this.editedItem.photo,this.editedItem.photo.name);
                formData.append('user',item.id);
                axios.post('/api/user/photo',formData)
                .then(res=>{
                       this.users.data[index].photo = res.data.user.photo;
                       this.editedItem.photo = null;
                }).catch(err=>console.log(err.response) )
            }

        },
        updateRole(item){
             const index = this.users.data.indexOf(item);
             axios.post(`api/user/role`,
             {'role':item.role,"user":item.id})
             .then(res=>{
                 this.text     = res.data.user.name+'\'s User Role Updated To '+res.data.user.role;
                 this.snackbar = true;
                 this.users.data[index].role = res.data.user.role;
             })
             .catch(err=>{
                this.text     = err.response.data.user.name+"\'s Role Cannot be Updated to "+err.response.data.user.role;
                this.snackbar = true;
                this.users.data[index].role = err.response.data.user.role;
                console.dir(err.response);
             });
        },
        checkEmail(){
            if(/.+@.+\..+/.test(this.editedItem.email))
            {
                axios.post(`/api/email/validate`,
                {'email':this.editedItem.email})
                 .then(res=>{
                     console.log(res);
                     this.success = res.data.message;
                     this.error   = '';
                })
                .catch(error=>{
                     this.success = '';
                     this.error   = "Email Already Exists";
                });
            }
        },
        selectAll(e){
           this.selected =[];
           if(e.length > 0)
               this.selected = e.map(val => val.id);
           console.dir(this.selected);
        },
        deleteAll(){

            let decide = confirm("Are you sure you want to delete these item?");

            if (decide) {
                axios.post(`/api/users/delete`, {
                    'users':this.selected
                })
                .then(res => {
                    console.log(res);
                    this.text     = res.data.message;
                    this.selected.forEach(e=>{
                        const index  = this.users.data.indexOf(e);
                        this.users.data.splice(index,1);
                    });
                    this.snackbar = true;

                }).catch(err => console.log(err.response));
            }
        },
        searchIt(event){
             console.log(event);
             if(event.length > 3)
             {
                  axios.get(`/api/users/${event}`)
                  .then(res=>{
                      console.log(res);
                      this.users=res.data.users;
                  })
                  .catch(err=>console.log(err.response))
             }
             if(event.length <= 0){
                  axios.get(`/api/users`)
                  .then(res=>this.users=res.data.users)
                  .catch(err=>console.log(err.response))
             }
        },
        paginate(event) {
            console.dir(this.options);
            const sortBy  = this.options.sortBy.length == 0 ? "name":this.options.sortBy[0];
            const orderBy = this.options.sortDesc.length > 0 || this.options.sortDesc[0] ?'asc':'desc';
            //send request
            axios.get(`/api/users?page=${event.page}`, {
                params: { per_page: event.itemsPerPage,'sort_by':sortBy,'order_by':orderBy}})
            .then(response => {
                // set getting roles in roles array
                this.users = response.data.users;
                this.roles = response.data.roles;
            })
            .catch(err => {
                this.loading = false;
                // if response  is unauthorized
                if (err.response.status == 401) {
                    //remove token in localstorage
                    localStorage.removeItem("token");
                    //then redirect login page
                    this.$router.push("/login");
                }
            });
        },
        initialize() {
            // Add a request intercept or
            axios.interceptors.request.use(
                config => {
                    //Do something before request is sent
                    this.loading = true;
                    return config;
                },
                error => {
                    this.loading = false;
                    return Promise.reject(error);
                }
            );

            // Add a response interceptor
            axios.interceptors.response.use(
                response => {
                    this.loading = false;
                    return response;
                },
                error => {
                    this.loading = false;
                    return Promise.reject(error);
                }
            );
        },

        editItem(item) {
            this.editedIndex = this.users.data.indexOf(item);
            this.editedItem  = Object.assign({}, item);
            this.dialog = true;
        },

        deleteItem(item) {
            const index = this.users.data.indexOf(item);
            let decide = confirm("Are you sure you want to delete this item?");

            if (decide) {
                axios.delete(`/api/users/${item.id}`, {})
                .then(res => {
                    this.snackbar = true;
                    this.text = "User Deleted Successfully";
                    this.users.data.splice(index, 1);
                }).catch(err => console.log(err.response));
            }
        },

        close() {
            this.dialog = false;
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem);
                this.editedIndex = -1;
            });
        },

        save() {
            if (this.editedIndex > -1) {
                // to edit exist item
                axios.put(`api/users/${this.editedItem.id}`,
                this.editedItem)
                .then(res => {
                    console.log(res);
                    console.log(this.users);
                    this.snackbar = true;
                    this.text     = "User updated Successfully";
                    Object.assign(
                        this.users.data[this.editedIndex],
                        res.data.user
                    );

                })
                .catch(err => console.log(err.response));
            } else {
                //to add new item
                axios
                    .post("api/users", this.editedItem )
                    .then(res => {
                        console.log(this.users.data);
                         this.users.data.push(res.data.user);
                    })
                    .catch(err => console.log(err));
            }

            this.close();
        }
    }
};
</script>

<style scoped></style>
