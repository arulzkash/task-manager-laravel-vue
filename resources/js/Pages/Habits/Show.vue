<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link, router } from "@inertiajs/vue3";

defineOptions({ layout: AppLayout });

const props = defineProps({
    habit: Object,
    month: String, // YYYY-MM
    weeks: Array, // array of weeks, each 7 cells or null
});

const goMonth = (ym) => {
    router.get(
        `/habits/${props.habit.id}`,
        { month: ym },
        { preserveScroll: true, preserveState: true }
    );
};

const toggleDate = (date, allowed) => {
    if (!allowed) return;
    router.patch(
        `/habits/${props.habit.id}/entries/toggle`,
        { date },
        { preserveScroll: true }
    );
};

const prevMonth = () => {
    const [y, m] = props.month.split("-").map(Number);
    const d = new Date(y, m - 2, 1);
    const ym = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(
        2,
        "0"
    )}`;
    goMonth(ym);
};

const nextMonth = () => {
    const [y, m] = props.month.split("-").map(Number);
    const d = new Date(y, m, 1);
    const ym = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(
        2,
        "0"
    )}`;
    goMonth(ym);
};
</script>

<template>
    <div>
        <div
            style="
                display: flex;
                justify-content: space-between;
                align-items: center;
            "
        >
            <h2>Habit: {{ habit.name }}</h2>
            <Link href="/habits">Back</Link>
        </div>

        <div style="opacity: 0.7; margin-top: 6px">
            start: {{ habit.start_date
            }}<span v-if="habit.end_date"> | end: {{ habit.end_date }}</span>
        </div>

        <section
            style="
                margin: 12px 0;
                display: flex;
                gap: 10px;
                align-items: center;
                flex-wrap: wrap;
            "
        >
            <button type="button" @click="prevMonth">Prev</button>
            <strong>{{ month }}</strong>
            <button type="button" @click="nextMonth">Next</button>
        </section>

        <table
            border="1"
            cellpadding="10"
            cellspacing="0"
            style="border-collapse: collapse; width: 100%; max-width: 760px"
        >
            <thead>
                <tr>
                    <th>Mon</th>
                    <th>Tue</th>
                    <th>Wed</th>
                    <th>Thu</th>
                    <th>Fri</th>
                    <th>Sat</th>
                    <th>Sun</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="(week, wi) in weeks" :key="wi">
                    <td
                        v-for="(cell, ci) in week"
                        :key="ci"
                        style="vertical-align: top"
                    >
                        <div v-if="cell">
                            <button
                                type="button"
                                @click="toggleDate(cell.date, cell.allowed)"
                                :disabled="!cell.allowed"
                                style="
                                    width: 100%;
                                    text-align: left;
                                    padding: 8px;
                                "
                            >
                                <div>
                                    <strong>{{ cell.day }}</strong>
                                </div>
                                <div style="margin-top: 6px">
                                    {{ cell.done ? "✅ done" : "❌" }}
                                </div>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 10px; opacity: 0.7">
            Klik tanggal untuk toggle done (cuma boleh dalam range start_date
            sampai end_date/today).
        </div>
    </div>
</template>
