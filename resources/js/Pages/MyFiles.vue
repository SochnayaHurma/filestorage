<template>
    <AuthenticatedLayout>
        <nav class="flex items-center justify-between p-1 mb-3">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li v-for="ancestor of ancestors.data"
                    :key="ancestor.id"
                    class="inline-flex items-center">
                    <Link v-if="!ancestor.parent_id"
                          :href="route('myFiles')"
                          class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                        <HomeIcon class="w-4 h-4"/>
                        Мои файлы
                    </Link>
                    <div v-else class="flex items-center">
                        <svg aria-hidden="true" class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20"
                             xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                  d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                  clip-rule="evenodd"></path>
                        </svg>
                        <Link :href="route('myFiles', {folder: ancestor.path})"
                              class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                            {{ ancestor.name }}
                        </Link>
                    </div>
                </li>
            </ol>
            <div class="flex">
                <label class="flex items-center mr-3 " for="only_favourites">
                    Только избранное
                    <Checkbox class="ml-2"
                        @change="showOnlyFavourites"
                        v-model:checked="onlyFavourites"
                        id="only_favourites"/>
                </label>
                <ShareFileButton :all-selected="allSelected" :selected-ids="selectedIds" />
                <DownloadFileButton :all="allSelected" :ids="selectedIds"/>
                <DeleteFileButton :delete-all="allSelected" :delete-ids="selectedIds" @delete="onDelete"/>
            </div>
        </nav>
        <div class="flex-1 overflow-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100 border-b">

                <tr>

                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left w-[30px] pr-0">
                        <Checkbox @change="onSelectAllChange" v-model:checked="allSelected"/>
                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">

                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Название
                    </th>
                    <th v-if="search" class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Путь
                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Владелец
                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Последнее обновление
                    </th>
                    <th class="text-sm font-medium text-gray-900 px-6 py-4 text-left">
                        Размер
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr
                    v-for="file of allFiles.data"
                    :key="file.id"
                    @click="event => toggleFileSelect(file)"
                    @dblclick="openFolder(file)"
                    @change="event => onSelectCheckboxChange(file)"
                    class="border-b transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                    :class="(selected[file.id] || allSelected ? 'bg-blue-50' : 'bg-white')"
                >
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center w-[30px] pr-0">
                        <Checkbox v-model="selected[file.id]"
                                  :checked="selected[file.id] || allSelected"/>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-yellow-500">
                        <div @click.stop.prevent="addRemoveFavorite(file)">
                            <svg v-if="!file.is_favourite" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24"
                                 stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/>
                            </svg>

                            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                 class="w-6 h-6">
                                <path fill-rule="evenodd"
                                      d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"
                                      clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <FileIcon :file="file"/>
                        {{ file.name }}
                    </td>
                    <td v-if="search" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ file.path }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ file.owner }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ file.updated_at }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ file.size }}
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
import {router, Link, useForm, usePage} from "@inertiajs/vue3";
import {HomeIcon} from '@heroicons/vue/20/solid'
import FileIcon from "@/Components/app/FileIcon.vue";
import {computed, onMounted, onUpdated, ref} from "vue";
import {httpGet, httpPost} from "@/Helper/http-helper.js";
import Checkbox from "@/Components/Checkbox.vue";
import DeleteFileButton from "@/Components/DeleteFileButton.vue";
import DownloadFileButton from "@/Components/DownloadFileButton.vue";
import {emitter, ON_SEARCH, showSuccessNotification} from "@/event-bus.js";
import ShareFileButton from "@/Components/app/ShareFileButton.vue";
const props = defineProps({
    files: Object,
    folder: Object,
    ancestors: Object
});
const page = usePage();
const allSelected = ref(false);
const onlyFavourites = ref(false);
const selected = ref({});
const loadMoreIntersect = ref(null);
const allFiles = ref({
    data: props.files.data,
    next: props.files.links.next
});
const search = ref('');
const selectedIds = computed(() => Object.entries(selected.value).filter(el => el[1]).map(el => el[0]));
let params = null;

function showOnlyFavourites() {
    if (onlyFavourites.value) {
        params.set('favourites', onlyFavourites.value ? 1 : 0);
    } else {
        params.delete('favourites');
    }
    router.get(`${window.location.pathname}?${params.toString()}`);
}

function addRemoveFavorite(file){
    httpPost(route('file.addToFavourites'), {id: file.id})
        .then((response) => {
            file.is_favourite = !file.is_favourite;
            showSuccessNotification('Выделеный файл успешно добавлен в избраное')
        })
        .catch((error) => {
            console.log(error);
        })
}

onMounted(() => {
    params = new URLSearchParams(window.location.search);
    onlyFavourites.value = params.get('favourites') ===  '1';
    search.value = params.get('search');
    emitter.on(ON_SEARCH, (value) => search.value = value);
    const observer = new IntersectionObserver(
        (entries) => entries.forEach(entry => entry.isIntersecting && loadMore()),
        {
            rootMargin: '-250px 0px 0px 0px'
        }
    );
    observer.observe(loadMoreIntersect.value);
});

function openFolder(file) {
    if (!file.is_folder) {
        return;
    }
    router.visit(route('myFiles', {folder: file.path}));
}

function loadMore() {
    if (allFiles.value.next == null) {
        return
    }
    httpGet(allFiles.value.next).then(response => {
        allFiles.value = {
            data: [...allFiles.value.data, ...response.data],
            next: response.links.next
        }
        // allFiles.value.data = [...allFiles.value.data, ...response.data];
        // allFiles.value.next = response.links.next;
    })

}

function onSelectAllChange() {
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
    } else {
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

function onDelete() {
    allSelected.value = false;
    selected.value = {};
}
</script>

