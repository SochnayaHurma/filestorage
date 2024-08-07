<template>
    <div class="h-screen bg-gray-50 flex w-full gap-4">
        <Navigation/>
        <main @drop.prevent="handleDrop"
              @dragover.prevent="onDragOver"
              @dragleave.prevent="onDragLeave"
              :class="dragOver ? 'dropzone' : ''"
              class="flex flex-col flex-1 px-4 overflow-hidden">
            <template v-if="dragOver" class="text-gray-500 text-center py-8 text-sm">
                Перетащите файлы сюда для загрузки.
            </template>
            <template v-else>
                <div class="flex items-center justify-between w-full">
                    <SearchForm/>
                    <UserSettingsDropdown/>
                </div>
                <div class="flex-1 flex flex-col overflow-hidden">
                    <slot/>
                </div>
            </template>
        </main>
    </div>
    <ErrorDialog />
    <FormProgress :form="fileUploadForm"/>
    <Notification />
</template>
<script setup>
import Navigation from "@/Components/app/Navigation.vue";
import SearchForm from "@/Components/app/SearchForm.vue";
import UserSettingsDropdown from "@/Components/app/UserSettingsDropdown.vue";
import {onMounted, ref} from "vue";
import {emitter, FILE_UPLOAD_STARTED, showErrorDialog, showSuccessNotification} from "@/event-bus.js";
import {useForm, usePage} from "@inertiajs/vue3";
import FormProgress from "@/Components/app/FormProgress.vue";
import ErrorDialog from "@/Components/ErrorDialog.vue";
import Notification from "@/Components/Notification.vue";

const page = usePage();
const dragOver = ref(false);
const fileUploadForm = useForm({
    files: [],
    relativePaths: [],
    parent_id: null
});


function handleDrop(event) {
    const files = event.dataTransfer.files;
    console.log(files);

    if (!files.length) {
        return;
    }

    uploadFiles(files);
}

function onDragOver() {
    dragOver.value = true;
}

function onDragLeave() {
    dragOver.value = false;
}

function uploadFiles(files) {
    fileUploadForm.parent_id = page.props.folder.id;
    fileUploadForm.files = files;
    fileUploadForm.relativePaths = [...files].map(file => file.webkitRelativePath)

    fileUploadForm.post(route('file.store'), {
        onSuccess: () => {
            showSuccessNotification(`Выбраные ${files.length} файлов были успешно загружены`);
        },
        onError: (errors) => {
            console.log('ERRORS')
            let message = '';
            if (Object.keys(errors).length > 0) {
                message = errors[Object.keys(errors)[0]];
            } else {
                message = 'Произошла ошибка во время загрузки файла. Пожалуйста попробуйте снова.';
            }
            showErrorDialog(message)
        },
        onFinish: () => {
            fileUploadForm.clearErrors();
            fileUploadForm.reset()
        }
    });
}

onMounted(() => {
    emitter.on(FILE_UPLOAD_STARTED, uploadFiles)
})
</script>
<style scoped>
    .dropzone {
        width: 100%;
        height: 100%;
        color: #8d8d8d;
        border: 2px dashed gray;
        display: flex;
        justify-content: center;
        align-items: center;
    }
</style>
