<template>
    <MenuItem
        v-slot="{ active }"
    >
        <a href="#"
            class="text-gray-700 block px-4 py-2 text-sm relative">
            Загрузить папку
            <input class="absolute left-0 top-0 right-0 bottom-0 cursor-pointer opacity-0"
                type="file" @change="onChange" multiple directory webkitdirectory>
        </a>
    </MenuItem>
</template>
<script setup>
import {MenuItem} from "@headlessui/vue";
import {emitter, FILE_UPLOAD_STARTED} from "@/event-bus.js";

function onChange(event) {
    emitter.emit(FILE_UPLOAD_STARTED, event.target.files);
}
</script>

<style scoped>

</style>
