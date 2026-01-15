<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link, useForm, router, Head } from "@inertiajs/vue3";
import { ref, computed } from "vue";

defineOptions({ layout: AppLayout });

const props = defineProps({
    week: Object,
    days: Array,
});

// --- NAVIGATION ---
const goWeek = (start) => {
    router.get(
        "/timeblocks",
        { week_start: start },
        { preserveState: true, preserveScroll: true }
    );
};

const prevWeek = () => {
    const d = new Date(props.week.start);
    d.setDate(d.getDate() - 7);
    goWeek(d.toISOString().slice(0, 10));
};

const nextWeek = () => {
    const d = new Date(props.week.start);
    d.setDate(d.getDate() + 7);
    goWeek(d.toISOString().slice(0, 10));
};

const formatDateHeader = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString("en-US", {
        weekday: "short",
        day: "numeric",
    });
};

const isToday = (dateStr) => {
    const today = new Date().toISOString().slice(0, 10);
    return dateStr === today;
};

// --- CRUD LOGIC (MODAL) ---
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    date: "",
    start_time: "09:00",
    end_time: "10:00",
    title: "",
    note: "",
});

const openCreateModal = (dateStr) => {
    isEditing.value = false;
    form.reset();
    form.date = dateStr;
    showModal.value = true;
};

const openEditModal = (block) => {
    isEditing.value = true;
    editingId.value = block.id;
    form.date = block.date;
    form.start_time = block.start_time;
    form.end_time = block.end_time;
    form.title = block.title;
    form.note = block.note ?? "";
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    editingId.value = null;
};

const submitForm = () => {
    if (isEditing.value) {
        form.patch(`/timeblocks/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post("/timeblocks", {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const deleteBlock = () => {
    if (confirm("Abort this tactical operation?")) {
        router.delete(`/timeblocks/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const getDuration = (start, end) => {
    const [h1, m1] = start.split(":").map(Number);
    const [h2, m2] = end.split(":").map(Number);
    const diff = h2 * 60 + m2 - (h1 * 60 + m1);
    const h = Math.floor(diff / 60);
    const m = diff % 60;
    if (h > 0 && m > 0) return `${h}h ${m}m`;
    if (h > 0) return `${h}h`;
    return `${m}m`;
};
</script>

<template>
    <Head title="Battle Plan" />

    <div class="p-4 md:p-6 min-h-screen flex flex-col">
        <div
            class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4"
        >
            <div>
                <h1
                    class="text-3xl font-black text-white tracking-tight flex items-center gap-2"
                >
                    <span>⏳</span> Weekly Battle Plan
                </h1>
                <div
                    class="text-slate-400 text-sm font-mono mt-1 flex items-center gap-2"
                >
                    <span>{{ week.start }}</span>
                    <span class="text-slate-600">➜</span>
                    <span>{{ week.end }}</span>
                </div>
            </div>

            <div
                class="flex bg-slate-800 rounded-lg p-1 border border-slate-700"
            >
                <button
                    @click="prevWeek"
                    class="px-4 py-2 hover:bg-slate-700 text-slate-300 rounded-md transition-colors"
                >
                    ◀ Prev
                </button>
                <button
                    @click="goWeek(new Date().toISOString().slice(0, 10))"
                    class="px-4 py-2 hover:bg-slate-700 text-indigo-400 font-bold rounded-md transition-colors border-x border-slate-700"
                >
                    Today
                </button>
                <button
                    @click="nextWeek"
                    class="px-4 py-2 hover:bg-slate-700 text-slate-300 rounded-md transition-colors"
                >
                    Next ▶
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-7 gap-4 flex-1">
            <div
                v-for="day in days"
                :key="day.date"
                class="flex flex-col gap-2 min-h-[200px]"
            >
                <div
                    class="p-3 rounded-xl border flex flex-col items-center justify-center transition-colors relative overflow-hidden group"
                    :class="
                        isToday(day.date)
                            ? 'bg-indigo-900/30 border-indigo-500 text-indigo-300 shadow-[0_0_15px_rgba(99,102,241,0.2)]'
                            : 'bg-slate-800 border-slate-700 text-slate-400'
                    "
                >
                    <div class="text-xs uppercase font-bold tracking-widest">
                        {{ formatDateHeader(day.date).split(" ")[0] }}
                    </div>
                    <div class="text-xl font-black">
                        {{ formatDateHeader(day.date).split(" ")[1] }}
                    </div>

                    <button
                        @click="openCreateModal(day.date)"
                        class="absolute inset-0 bg-indigo-500/0 hover:bg-indigo-500/10 transition-colors flex items-center justify-center"
                        title="Add Task"
                    >
                        <span
                            class="opacity-0 group-hover:opacity-100 text-white text-2xl font-bold"
                            >+</span
                        >
                    </button>
                </div>

                <div class="flex-1 space-y-2">
                    <div
                        v-for="block in day.items"
                        :key="block.id"
                        @click="openEditModal(block)"
                        class="bg-slate-800 border-l-4 border-indigo-500 p-3 rounded-r-xl hover:bg-slate-700 cursor-pointer transition-all hover:translate-x-1 shadow-md group relative overflow-hidden"
                    >
                        <div
                            class="absolute top-0 right-0 w-16 h-16 bg-white/5 rounded-full blur-2xl -mr-8 -mt-8 pointer-events-none"
                        ></div>

                        <div class="flex items-baseline gap-1.5 mb-1">
                            <span
                                class="text-xl font-black text-white font-mono tracking-tighter leading-none"
                            >
                                {{ block.start_time }}
                            </span>

                            <span class="text-xs text-slate-500 font-bold"
                                >➜</span
                            >

                            <span
                                class="text-sm font-bold text-slate-400 font-mono"
                            >
                                {{ block.end_time }}
                            </span>
                        </div>

                        <div
                            class="font-bold text-indigo-300 text-sm leading-tight pr-4"
                        >
                            {{ block.title }}
                        </div>

                        <div class="flex justify-between items-end mt-2">
                            <div
                                v-if="block.note"
                                class="text-[10px] text-slate-500 truncate max-w-[70%] italic"
                            >
                                "{{ block.note }}"
                            </div>
                            <div v-else></div>

                            <div
                                class="text-[9px] font-bold text-slate-400 bg-slate-900/50 px-1.5 py-0.5 rounded border border-slate-700/50"
                            >
                                {{
                                    getDuration(
                                        block.start_time,
                                        block.end_time
                                    )
                                }}
                            </div>
                        </div>
                    </div>

                    <button
                        v-if="day.items.length === 0"
                        @click="openCreateModal(day.date)"
                        class="w-full h-full min-h-[100px] border-2 border-dashed border-slate-800 rounded-xl flex flex-col items-center justify-center text-slate-600 hover:border-slate-600 hover:text-slate-400 transition-colors gap-2 group"
                    >
                        <span
                            class="text-2xl opacity-50 group-hover:scale-110 transition-transform"
                            >+</span
                        >
                        <span
                            class="text-[10px] uppercase font-bold tracking-widest"
                            >Free</span
                        >
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4 animate-fade-in"
        >
            <div
                class="bg-slate-800 border border-slate-600 rounded-xl w-full max-w-md shadow-2xl overflow-hidden"
            >
                <div
                    class="bg-slate-900/50 px-6 py-4 border-b border-slate-700 flex justify-between items-center"
                >
                    <h3 class="text-lg font-bold text-white">
                        {{ isEditing ? "Edit Operation" : "New Operation" }}
                    </h3>
                    <button
                        @click="closeModal"
                        class="text-slate-400 hover:text-white"
                    >
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <div>
                        <label
                            class="block text-xs uppercase text-slate-500 font-bold mb-1"
                            >Date</label
                        >
                        <input
                            type="date"
                            v-model="form.date"
                            class="input-dark w-full"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs uppercase text-slate-500 font-bold mb-1"
                                >Start</label
                            >
                            <input
                                type="time"
                                v-model="form.start_time"
                                class="input-dark w-full text-center font-bold tracking-widest"
                                required
                            />
                        </div>
                        <div>
                            <label
                                class="block text-xs uppercase text-slate-500 font-bold mb-1"
                                >End</label
                            >
                            <input
                                type="time"
                                v-model="form.end_time"
                                class="input-dark w-full text-center font-bold tracking-widest"
                                required
                            />
                        </div>
                    </div>
                    <div
                        v-if="form.errors.end_time"
                        class="text-red-400 text-xs text-center"
                    >
                        {{ form.errors.end_time }}
                    </div>

                    <div>
                        <label
                            class="block text-xs uppercase text-slate-500 font-bold mb-1"
                            >Mission Title</label
                        >
                        <input
                            v-model="form.title"
                            placeholder="e.g. Deep Work: Backend"
                            class="input-dark w-full font-bold text-white"
                            required
                        />
                        <div
                            v-if="form.errors.title"
                            class="text-red-400 text-xs mt-1"
                        >
                            {{ form.errors.title }}
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-xs uppercase text-slate-500 font-bold mb-1"
                            >Briefing Note</label
                        >
                        <textarea
                            v-model="form.note"
                            rows="3"
                            class="input-dark w-full resize-none"
                            placeholder="Details..."
                        ></textarea>
                    </div>

                    <div
                        class="flex justify-between items-center pt-4 border-t border-slate-700"
                    >
                        <button
                            v-if="isEditing"
                            type="button"
                            @click="deleteBlock"
                            class="text-red-400 hover:text-red-300 text-xs font-bold uppercase hover:underline"
                        >
                            Delete
                        </button>
                        <div v-else></div>

                        <div class="flex gap-3">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 text-slate-400 hover:text-white transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2 rounded-lg font-bold shadow-lg transition-all"
                            >
                                {{ isEditing ? "Update Plan" : "Confirm Plan" }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.input-dark {
    @apply bg-slate-900 border border-slate-700 text-slate-200 rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-indigo-500 transition-all placeholder-slate-500;
}
.animate-fade-in {
    animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
input[type="date"]::-webkit-calendar-picker-indicator,
input[type="time"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.6;
    cursor: pointer;
}
</style>
