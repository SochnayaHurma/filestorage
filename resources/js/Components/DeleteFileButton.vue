<template>
    <button @click="onDeleteClick" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200
    rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700
    focus:text-blue-700">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
             class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
        </svg>
        Удалить
    </button>
    <ConfirmationDialog :show="showDeleteDialog"
                        message="Вы уверены что хотите удалить данные файлы?"
                        @cancel="onDeleteCancel"
                        @confirm="onDeleteConfirm">

    </ConfirmationDialog>
</template>
<script setup>

import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import {ref} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import {showErrorDialog, showSuccessNotification} from "@/event-bus.js";

const props = defineProps({
    deleteAll: {
        type: Boolean,
        required: false,
        default: false
    },
    deleteIds:  {
        type: Array,
        required: false
    }
});

const deleteFilesForm = useForm({
    all: null,
    ids: [],
    parent_id: null
});
const showDeleteDialog = ref(false);
const emit = defineEmits(['delete'])
const page = usePage();
function onDeleteClick(event) {
    if (!props.deleteAll && !props.deleteIds.length) {
        showErrorDialog('Вам нужно выделить файл для удаления');
        return;
    }
    console.log("Delete", props.deleteAll, props.deleteIds)
    showDeleteDialog.value = true;
}

function onDeleteCancel() {
  showDeleteDialog.value = false;
}

function onDeleteConfirm() {
    deleteFilesForm.parent_id = page.props.folder.id;
    if (props.deleteAll) {
        deleteFilesForm.all = true;
    } else {
        deleteFilesForm.ids = props.deleteIds;
    }
    deleteFilesForm.delete(route('file.delete'), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            emit('delete');
            // Todo show success message
            showSuccessNotification('Файл успешно удален');
        }
    });
}
</script>
<style scoped>

</style>
