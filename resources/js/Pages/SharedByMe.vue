<template>
    <AuthenticatedLayout>
        <nav class="flex items-center justify-end p-1 mb-3">
            <div>
                <DownloadFileButton :all="allSelected" :ids="selectedIds" :shared-by-me="true"/>
            </div>
        </nav>
        <div class="flex-1 overflow-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left w-[30px] pr-0">
                        <Checkbox @change="onSelectAllChange" v-model:checked="allSelected" />
                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Название
                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Расположение
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr
                    v-for="file of allFiles.data"
                    :key="file.id"
                    @click="event => toggleFileSelect(file)"
                    @change="event => onSelectCheckboxChange(file)"
                    class="border-b transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                    :class="(selected[file.id] || allSelected ? 'bg-blue-50' : 'bg-white')"
                >
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center w-[30px] pr-0">
                        <Checkbox v-model="selected[file.id]"
                                  :checked="selected[file.id] || allSelected" />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <FileIcon :file="file" />
                        {{ file.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ file.path }}
                    </td>
                </tr>
                </tbody>
            </table>
            <div v-if="!allFiles.data.length" class="py-8 text-center text-sm text-gray-400">
                В этой папке ничего нет
            </div>
            <div ref="loadMoreIntersect"></div>
        </div>

    </AuthenticatedLayout>
</template>
<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {router, Link} from "@inertiajs/vue3";
import { HomeIcon } from '@heroicons/vue/20/solid'
import FileIcon from "@/Components/app/FileIcon.vue";
import {computed, onMounted, onUpdated, ref} from "vue";
import {all} from "axios";
import {httpGet} from "@/Helper/http-helper.js";
import Checkbox from "@/Components/Checkbox.vue";
import DeleteFileButton from "@/Components/DeleteFileButton.vue";
import DownloadFileButton from "@/Components/DownloadFileButton.vue";
import RestoreFileButton from "@/Components/app/RestoreFileButton.vue";
import DeleteForeverButton from "@/Components/app/DeleteForeverButton.vue";
const props = defineProps({
    files: Object,
    folder: Object,
    ancestors: Object
});
const allSelected = ref(false);
const selected = ref({});
const loadMoreIntersect = ref(null);
const allFiles = ref({
    data: props.files.data,
    next: props.files.links.next
});
const selectedIds = computed(() => Object.entries(selected.value).filter(el => el[1]).map(el => el[0]));


onMounted(() => {
    console.log('mount ')
    const observer = new IntersectionObserver(
        (entries) => entries.forEach(entry => entry.isIntersecting && loadMore() ),
        {
            rootMargin: '-250px 0px 0px 0px'
        }
    );
    observer.observe(loadMoreIntersect.value);
});

function loadMore() {
    if (allFiles.value.next == null) {
        return
    }
    httpGet(allFiles.value.next).then(response => {
        allFiles.value = {
            data: [...allFiles.value.data, ...response.data],
            next: response.links.next
        }
    })

}
function onSelectAllChange(){
    allFiles.value.data.forEach(file => {
        selected.value[file.id] = allSelected.value;
    })
}

function toggleFileSelect(file) {
    selected.value[file.id] = !selected.value[file.id];
    onSelectCheckboxChange(file);
}

function onSelectCheckboxChange(file) {
    if (!selected.value[file.id]) {
        allSelected.value = false;
        console.log('qwe')
    } else {
        console.log('asd')
        let checked = true;
        for (let file_iter of allFiles.value.data) {
            if (!selected.value[file_iter.id]) {
                checked = false;
                break;
            }
        }
        allSelected.value = checked;
    }
}
function resetForm() {
    allSelected.value = false;
    selected.value = {};
}
</script>

