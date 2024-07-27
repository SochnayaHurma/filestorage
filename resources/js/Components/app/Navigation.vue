<template>
    <nav class="min-w-[200px]">
        <Link
            :href="route('myFiles')"
            class="h-[80px] px-3 flex items-center gap-3">
            <ApplicationLogo
                class="block h-9 w-auto fill-current text-gray-800"
            />
            FileStorage
        </Link>
        <div class="px-3">
            <CreateNewDropdown />
            <div class="py-3">
                <NavLink :active="$page.props.route_name === 'myFiles'" :href="route('myFiles')">Мои файлы</NavLink>
                <NavLink :active="$page.props.route_name === 'sharedWithMe'" :href="route('sharedWithMe')" >Опубликованые для меня</NavLink>
                <NavLink :active="$page.props.route_name === 'sharedByMe'" :href="route('sharedByMe')">Опубликованые мной</NavLink>
                <NavLink :active="$page.props.route_name === 'trash'" :href="route('trash')">Корзина</NavLink>
            </div>
        </div>
    </nav>
</template>
<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import CreateNewDropdown from "@/Components/app/CreateNewDropdown.vue";
import { Link } from "@inertiajs/vue3";
import NavLink from "@/Components/NavLink.vue";
</script>
