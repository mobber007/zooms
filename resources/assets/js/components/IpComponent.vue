<template>
    <div class="content text-center">
        <div class="title m-b-md">
            {{country}}
        </div>

            <div class="form-group row" style="max-width: 300px; min-width: 250px; margin: 0 auto">
                <input  type="url" v-model="ip" class="form-control col-9" id="ip" placeholder="Insert IP here" required>
                <button  class="col-3 btn btn-outline-info" v-on:click="get_country">GO</button>
            </div>

    </div>
</template>

<script>
    export default {
        data: () =>
            ({
                ip: '',
                country: ''
            }),
        mounted() {
            this.get_country()
        }
        ,
        methods:
            {
                get_country()
                {
                    axios.get('/ip/' + this.ip
                    )
                        .then( response => {
                            this.country = response.data.country
                            this.ip = response.data.ip
                        }

                    )
                }
            }
    }
</script>
