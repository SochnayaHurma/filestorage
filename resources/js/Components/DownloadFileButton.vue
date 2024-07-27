<template>
    <PrimaryButton @click="download">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
             class="w-6 h-6 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="m9 13.5 3 3m0 0 3-3m-3 3v-6m1.06-4.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/>
        </svg>
        Загрузить
    </PrimaryButton>
</template>
<script setup>

import {useForm, usePage} from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {httpGet} from "@/Helper/http-helper.js";

const props = defineProps({
    all: {
        type: Boolean,
        required: false,
        default: false
    },
    ids: {
        type: Array,
        required: false
    },
    sharedWithMe: false,
    sharedByMe: false,
});

const page = usePage();

function download(event) {
    if(!props.all && props.ids.length === 0) {
        return;
    }
    const params = new URLSearchParams();
    if (page.props.folder?.id) {
        params.append('parent_id', page.props.folder?.id);
    }
    if (props.all) {
        const isAll = props.all ? 1 : 0;
        params.append('all', isAll);
    } else {
        for (let id of props.ids) {
            params.append('ids[]', id);
        }
    }
    let url = route('file.download');
    if (props.sharedWithMe) {
        url = route('file.downloadSharedWithMe');
    } else if (props.sharedByMe) {
        url = route('file.downloadSharedByMe');
    }
    httpGet(`${url}?${params.toString()}`)
        .then((response) => {
            if (!response.url) return;
            const link = document.createElement('a');
            link.download = response.filename;
            link.href = response.url;
            link.click();
        })
}
</script>
