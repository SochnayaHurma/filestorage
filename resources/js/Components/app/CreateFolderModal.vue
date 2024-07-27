<template>
    <modal :show="modelValue" @show="onShow"  max-width="sm">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                Создать новую папку
            </h2>
            <div class="mt-6">
                <InputLabel for="folderName" value="Наименование папки" class="sr-only " />
                <TextInput
                    type="text"
                    id="folderName"
                    ref="folderNameInput"
                    v-model="form.name"
                    class="mt-1 block w-full"
                    :class="form.errors.name
                            ? 'border-red-500 focus:border-red-500 focus:ring-red-500': ''"
                    placeholder="Наименование папки"
                    @keyup.enter="createFolder"
                />
                <InputError
                    :message="form.errors.name"
                    class="mt-2"
                />
            </div>
            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal">Назад</SecondaryButton>
                <PrimaryButton
                    class="ml-3"
                    @click="createFolder"
                    :disable="form.processing"
                    :class="{'opacity-25': form.processing}"
                >
                    Создать
                </PrimaryButton>
            </div>
        </div>
    </modal>
</template>
<script setup>
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {nextTick, ref} from "vue";
import {showSuccessNotification} from "@/event-bus.js";

const form = useForm({
    name: '',
    parent_id: null
})
const emit = defineEmits(['update:modelValue'])
const page = usePage();
const folderNameInput = ref(null);
const {modelValue} = defineProps({
    modelValue: Boolean
})

function onShow() {
    nextTick(() => folderNameInput.value.focus())

}

function createFolder() {
    let name = form.name;
    form.parent_id = page.props.folder.id;
    form.post(route('folder.create'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
            showSuccessNotification(`Папка '${name}' успешно создана`);
        },
        onError: () => {
            folderNameInput.value.focus();
        }
    })
}
function closeModal() {
    emit('update:modelValue');
    form.clearErrors();
    form.reset();
}

</script>
