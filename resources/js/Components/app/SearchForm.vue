<template>
    <div class="w-[600px] h-[80px] flex items-center">
        <TextInput
            type="text"
            class="block w-full mr-2"
            v-model="search"
            autocomplete
            placeholder="Search for files and folders"
            @keyup.enter="onSearch"
        />
    </div>
</template>
<script setup>
import TextInput from "@/Components/TextInput.vue";
import {router, useForm} from "@inertiajs/vue3";
import {onMounted, ref} from "vue";
import {emitter, ON_SEARCH} from "@/event-bus.js";

const search = ref('');
let params = '';

onMounted(() => {
    params = new URLSearchParams(window.location.search);
    search.value = params.get('search');
})
function onSearch(event) {
    params.set('search', search.value)
    router.get(`${window.location.pathname}?${params.toString()}`);
    emitter.emit(ON_SEARCH, search.value);
}
</script>
