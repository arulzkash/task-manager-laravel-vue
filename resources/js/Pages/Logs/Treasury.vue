<script setup>
import { Link, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";
import AppLayout from '@/Layouts/AppLayout.vue'
defineOptions({ layout: AppLayout })

const props = defineProps({
    logs: Object, // paginator
    filters: Object,
    rewardOptions: Array, // [{id,name}]
});

const filterForm = useForm({
    period: props.filters?.period ?? "all",
    date: props.filters?.date ?? "",
    from: props.filters?.from ?? "",
    to: props.filters?.to ?? "",
    reward_id: props.filters?.reward_id ?? "",
    sort: props.filters?.sort ?? "purchased_at",
    dir: props.filters?.dir ?? "desc",
});

const apply = () => {
    router.get("/logs/treasury", filterForm.data(), {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearCustomDates = () => {
    filterForm.date = "";
    filterForm.from = "";
    filterForm.to = "";
};

const setPeriod = (p) => {
    filterForm.period = p;
    clearCustomDates();
    apply();
};

const resetAll = () => {
    filterForm.period = "all";
    filterForm.date = "";
    filterForm.from = "";
    filterForm.to = "";
    filterForm.reward_id = "";
    filterForm.sort = "purchased_at";
    filterForm.dir = "desc";
    apply();
};

// edit note
const editingId = ref(null);
const editForm = ref(null);

const startEdit = (log) => {
    editingId.value = log.id;
    editForm.value = useForm({ note: log.note ?? "" });
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.value = null;
};

const saveEdit = () => {
    editForm.value.patch(`/logs/treasury/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    });
};
</script>

<template>
    <div style="padding: 16px">
        <div
            style="
                display: flex;
                justify-content: space-between;
                align-items: center;
            "
        >
            <h2>Treasury Purchase Log</h2>
            
        </div>

        <section
            style="
                margin: 12px 0;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 8px;
            "
        >
            <strong>Filter & Sort</strong>

            <div
                style="
                    display: flex;
                    gap: 10px;
                    margin-top: 8px;
                    flex-wrap: wrap;
                "
            >
                <button type="button" @click="setPeriod('all')">All</button>
                <button type="button" @click="setPeriod('today')">Today</button>
                <button type="button" @click="setPeriod('7d')">
                    Last 7 days
                </button>
                <button type="button" @click="setPeriod('month')">
                    This month
                </button>
            </div>

            <div
                style="
                    display: flex;
                    gap: 12px;
                    flex-wrap: wrap;
                    margin-top: 12px;
                    align-items: end;
                "
            >
                <div>
                    <div>Pick date</div>
                    <input type="date" v-model="filterForm.date" />
                </div>

                <div>
                    <div>From</div>
                    <input type="date" v-model="filterForm.from" />
                </div>

                <div>
                    <div>To</div>
                    <input type="date" v-model="filterForm.to" />
                </div>

                <div>
                    <div>Reward</div>
                    <select v-model="filterForm.reward_id">
                        <option value="">All</option>
                        <option
                            v-for="r in rewardOptions"
                            :key="r.id"
                            :value="String(r.id)"
                        >
                            {{ r.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <div>Sort</div>
                    <select v-model="filterForm.sort">
                        <option value="purchased_at">purchased_at</option>
                        <option value="cost_coin">cost_coin (total)</option>
                        <option value="qty">qty</option>
                        <option value="created_at">created_at</option>
                    </select>
                </div>

                <div>
                    <div>Dir</div>
                    <select v-model="filterForm.dir">
                        <option value="desc">desc</option>
                        <option value="asc">asc</option>
                    </select>
                </div>

                <div style="display: flex; gap: 8px">
                    <button type="button" @click="apply">Apply</button>
                    <button type="button" @click="resetAll">Reset</button>
                </div>
            </div>

            <div style="margin-top: 8px; opacity: 0.7">
                Kalau date / from-to diisi, itu override period.
            </div>
        </section>

        <div v-if="logs.data.length === 0">Belum ada purchase.</div>

        <table
            v-else
            border="1"
            cellpadding="8"
            cellspacing="0"
            style="width: 100%; border-collapse: collapse"
        >
            <thead>
                <tr>
                    <th>Purchased at</th>
                    <th>Reward</th>
                    <th>Qty</th>
                    <th>Unit cost</th>
                    <th>Total cost</th>
                    <th>Note</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="log in logs.data" :key="log.id">
                    <td>{{ log.purchased_at }}</td>
                    <td>{{ log.reward?.name ?? "(deleted)" }}</td>
                    <td>{{ log.qty }}</td>
                    <td>{{ log.unit_cost_coin ?? "-" }}</td>
                    <td>{{ log.cost_coin }}</td>

                    <td style="min-width: 280px">
                        <template v-if="editingId === log.id">
                            <textarea
                                v-model="editForm.note"
                                rows="2"
                                style="width: 100%"
                            ></textarea>
                            <div
                                v-if="editForm.errors.note"
                                style="color: #b00020"
                            >
                                {{ editForm.errors.note }}
                            </div>
                        </template>
                        <template v-else>
                            <span v-if="log.note">{{ log.note }}</span>
                            <span v-else style="opacity: 0.7">(No note)</span>
                        </template>
                    </td>

                    <td>
                        <template v-if="editingId === log.id">
                            <button
                                type="button"
                                @click="saveEdit"
                                :disabled="editForm.processing"
                            >
                                Save
                            </button>
                            <button
                                type="button"
                                @click="cancelEdit"
                                style="margin-left: 8px"
                            >
                                Cancel
                            </button>
                        </template>
                        <template v-else>
                            <button type="button" @click="startEdit(log)">
                                Edit note
                            </button>
                        </template>
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px">
            <Link
                v-for="link in logs.links"
                :key="link.label"
                :href="link.url || ''"
                v-html="link.label"
                :style="{
                    padding: '6px 10px',
                    border: '1px solid #ddd',
                    borderRadius: '6px',
                    textDecoration: 'none',
                    opacity: link.url ? 1 : 0.4,
                    pointerEvents: link.url ? 'auto' : 'none',
                    background: link.active ? '#eee' : 'transparent',
                }"
            />
        </div>
    </div>
</template>
