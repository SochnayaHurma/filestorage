<template>
    <modal :show="props.modelValue" @show="onShow"  max-width="sm">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                Поделится файлами
            </h2>
            <div class="mt-6">
                <InputLabel for="shareEmail" value="Введите адрес электроной почты" class="sr-only " />
                <TextInput
                    type="text"
                    id="shareEmail"
                    ref="emailInput"
                    v-model="form.email"
                    class="mt-1 block w-full"
                    :class="form.errors.email
                            ? 'border-red-500 focus:border-red-500 focus:ring-red-500': ''"
                    placeholder="bob@example.com"
                    @keyup.enter="share"
                />
                <InputError :message="form.errors.email" class="mt-2"/>
            </div>
            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal">Назад</SecondaryButton>
                <PrimaryButton
                    class="ml-3"
                    @click="share"
                    :disable="form.processing"
                    :class="{'opacity-25': form.processing}"
                >
                    Поделится
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
    all: false,
    ids: [],
    email: null,
    parent_id: null
})
const emit = defineEmits(['update:modelValue'])
const page = usePage();
const emailInput = ref(null);
const props = defineProps({
    modelValue: Boolean,
    allSelected: Boolean,
    selectedIds: Array
})
function onShow() {
    nextTick(() => emailInput.value.focus())

}

function share() {
    form.parent_id = page.props.folder.id;
    if (props.allSelected){
        form.all = true;
        form.ids = [];
    } else {
        form.all = false;
        form.ids = props.selectedIds;
    }
    let email = form.email;
    form.post(route('file.share'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
            showSuccessNotification(`Вы успешно поделились файлом с пользователем ${email}`);
        },
        onError: () => {
            emailInput.value.focus();
        }
    })
}
function closeModal() {
    emit('update:modelValue');
    form.clearErrors();
    form.reset();
}

</script>
