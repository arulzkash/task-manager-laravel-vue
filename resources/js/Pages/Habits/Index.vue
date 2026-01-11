<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link, useForm, router } from "@inertiajs/vue3";

defineOptions({ layout: AppLayout });

const props = defineProps({
    habits: Array,
    filters: Object,
});

const createForm = useForm({
    name: "",
    start_date: "",
});

const toggleHabit = (id) => {
    router.patch(`/habits/${id}/toggle`, {}, { preserveScroll: true });
};

const archiveHabit = (h) => {
    const ok = window.confirm(`Archive habit "${h.name}"?`);
    if (!ok) return;
    router.patch(`/habits/${h.id}/archive`, {}, { preserveScroll: true });
};

const setView = (view) => {
    router.get(
        "/habits",
        { view },
        { preserveScroll: true, preserveState: true }
    );
};
</script>

<template>
    <div>
        <h2>Habits</h2>

        <section
            style="
                margin: 12px 0;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 8px;
            "
        >
            <strong>Create Habit</strong>
            <form
                @submit.prevent="createForm.post('/habits')"
                style="margin-top: 10px"
            >
                <div
                    style="
                        display: flex;
                        gap: 10px;
                        flex-wrap: wrap;
                        align-items: end;
                    "
                >
                    <div>
                        <div>Name</div>
                        <input
                            v-model="createForm.name"
                            placeholder="e.g. Belajar Vue"
                        />
                        <div
                            v-if="createForm.errors.name"
                            style="color: #b00020"
                        >
                            {{ createForm.errors.name }}
                        </div>
                    </div>

                    <div>
                        <div>Start date (optional)</div>
                        <input type="date" v-model="createForm.start_date" />
                        <div
                            v-if="createForm.errors.start_date"
                            style="color: #b00020"
                        >
                            {{ createForm.errors.start_date }}
                        </div>
                    </div>

                    <button type="submit" :disabled="createForm.processing">
                        Add
                    </button>
                </div>
            </form>
        </section>

        <section
            style="
                margin: 12px 0;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 8px;
            "
        >
            <strong>View</strong>
            <div
                style="
                    display: flex;
                    gap: 10px;
                    margin-top: 8px;
                    flex-wrap: wrap;
                "
            >
                <button
                    type="button"
                    @click="setView('active')"
                    :disabled="filters?.view === 'active'"
                >
                    Active
                </button>
                <button
                    type="button"
                    @click="setView('archived')"
                    :disabled="filters?.view === 'archived'"
                >
                    Archived
                </button>
                <button
                    type="button"
                    @click="setView('all')"
                    :disabled="filters?.view === 'all'"
                >
                    All
                </button>
            </div>
        </section>

        <section v-if="habits.length === 0" style="opacity: 0.7">
            Belum ada habits.
        </section>

        <ul v-else style="padding-left: 18px">
            <li v-for="h in habits" :key="h.id" style="margin: 12px 0">
                <label style="display: flex; gap: 10px; align-items: center">
                    <input
                        type="checkbox"
                        :checked="h.done_today"
                        @change="toggleHabit(h.id)"
                    />

                    <div style="flex: 1">
                        <div>
                            <strong>{{ h.name }}</strong>
                            <span style="opacity: 0.7">
                                (streak: {{ h.streak }})</span
                            >
                            <span style="margin-left: 8px"
                                >Today: {{ h.done_today ? "✅" : "❌" }}</span
                            >
                        </div>
                        <div style="opacity: 0.7; font-size: 13px">
                            start: {{ h.start_date
                            }}<span v-if="h.end_date">
                                | end: {{ h.end_date }}</span
                            >
                        </div>
                    </div>

                    <div style="display: flex; gap: 8px; flex-wrap: wrap">
                        <Link :href="`/habits/${h.id}`">Monthly</Link>
                        <button
                            v-if="!h.end_date"
                            type="button"
                            @click="archiveHabit(h)"
                        >
                            Archive
                        </button>
                    </div>
                </label>
            </li>
        </ul>
    </div>
</template>
