<template>
    <v-data-table
        item-key="name"
        class="elevation-1"
        color="error"
        :loading="loading"
        loading-text="Loading... Please wait"
        :headers="headers"
        :items="roles.data"
        :items-per-page="5"
        show-select
        :server-items-length="roles.total"
        @pagination="paginate"
        @input="selectAll"
        sort-by="calories"
        :footer-props="{
            itemsPerPageOptions: [5, 10, 15],
            itemsPerPageText: 'Roles Per Page',
            showCurrentPage :true,
            showFirstLastPage:true
        }"
    >
        <template v-slot:top>

            <v-toolbar flat color="dark">
                <v-toolbar-title>
                    Role Management System
                </v-toolbar-title>
                <v-divider class="mx-4" inset vertical></v-divider>
                <v-spacer></v-spacer>

                <v-dialog v-model="dialog" max-width="500px">

                    <template v-slot:activator="{ on }">
                        <v-btn color="error" dark class="mb-2" v-on="on">
                            Add New Role
                        </v-btn>
                         <v-btn color="error" @click="deleteAll" dark class="mb-2 mr-2">
                            Delete
                    </v-btn>

                    </template>
                    <v-card>
                        <v-card-title>
                            <span class="headline">{{ formTitle }}</span>
                        </v-card-title>

                        <v-card-text>
                            <v-container>
                                <v-row>
                                    <v-col cols="12" sm="12">
                                        <v-text-field
                                            color="error"
                                            v-model="editedItem.name"
                                            label="Role name"
                                        ></v-text-field>
                                    </v-col>


                                </v-row>
                            </v-container>
                        </v-card-text>

                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="error darken-1" text @click="close"
                                >Cancel</v-btn
                            >
                            <v-btn color="error darken-1" text @click="save"
                                >Save</v-btn
                            >
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
    data: () => ({
        dialog: false,
        loading: false,
        snackbar: false,
        selected:[],
        text: "this is default value",
        headers: [
            {
                text: "#",align: "start",sortable: false,value: "id"
            },
            { text: "Name", value: "name" },
            { text: "Created At", value: "created_at" },
            { text: "Updated At", value: "updated_at" },
            { text: "Actions", value: "actions", sortable: false }
        ],
        roles: [],
        editedIndex: -1,
        editedItem: {
            id: "",
            name: "",
            created_at: "",
            updated_at: ""
        },
        defaultItem: {
            id: "",
            name: "",
            created_at: "",
            updated_at: ""
        }
    }),

    computed: {
        formTitle() {
            return this.editedIndex === -1 ? "New Role" : "Edit Role";
        }
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
        selectAll(e){

           console.log(e);
           this.selected =[];
           if(e.length > 0){
               this.selected = e.map(val => val.id);
           }
           console.dir(this.selected);
        },
        deleteAll(){

           let decide = confirm("Are you sure you want to delete these item?");

            if (decide) {
                axios.post(`/api/roles/delete`, {
                    'roles':this.selected
                })
                .then(res => {
                    console.log(res);
                    this.text     = res.data.message;
                    this.selected.forEach(e=>{
                        const index  = this.roles.data.indexOf(e);
                        this.roles.data.splice(index,1);
                    });
                    this.snackbar = true;

                }).catch(err => console.log(err.response));
            }
        },
        searchIt(event){
             console.log(event);
             if(event.length > 3)
             {
                  axios.get(`/api/roles/${event}`)
                  .then(res=>{
                      console.log(res);
                      this.roles=res.data.roles;
                  })
                  .catch(err=>console.log(err.response))
             }
             if(event.length <= 0){
                  axios.get(`/api/roles`)
                  .then(res=>this.roles=res.data.roles)
                  .catch(err=>console.log(err.response))
             }
        },
        paginate(event) {
            console.dir(event);
            //send request
            axios.get(`/api/roles?page=${event.page}`, {
                params: { per_page: event.itemsPerPage }
            })
            .then(response => {
                // set getting roles in roles array
                // console.log(response);
                this.roles = response.data.roles;
                console.log(response.data.roles.data);

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
            this.editedIndex = this.roles.data.indexOf(item);
            this.editedItem = Object.assign({}, item);
            this.dialog = true;
        },

        deleteItem(item) {
            const index = this.roles.data.indexOf(item);
            let decide = confirm("Are you sure you want to delete this item?");

            if (decide) {
                axios.delete(`/api/roles/${item.id}`, {})
                .then(res => {
                    this.snackbar = true;
                    this.text = "Role Deleted Successfully";
                    this.roles.data.splice(index, 1);
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
                axios.put("api/roles/" + this.editedItem.id, {
                        name: this.editedItem.name
                })
                .then(res => {
                    Object.assign(
                        this.roles[this.editedIndex],
                        res.data.role
                    );
                    this.snackbar = true;
                    this.text     = "Role updated Successfully";
                })
                .catch(err => console.log(err.response));
            } else {
                //to add new item
                axios
                    .post("api/roles", { name: this.editedItem.name })
                    .then(res => {
                        console.log(this.roles.data);
                         this.roles.data.push(res.data.role);
                    })
                    .catch(err => console.log(err));
            }

            this.close();
        }
    }
};
</script>

<style scoped></style>
