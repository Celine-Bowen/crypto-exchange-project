<script setup>
const props = defineProps({
  orders: { type: Array, default: () => [] },
  refreshing: { type: Boolean, default: false },
});

const emit = defineEmits(['cancel']);

function formatStatus(order) {
  if (order.status) {
    return order.status.toLowerCase();
  }

  return order.status_code === 1 ? 'open' : 'filled';
}

function statusClasses(order) {
  const status = formatStatus(order);

  if (status === 'open') {
    return 'bg-amber-500/10 text-amber-200';
  }

  if (status === 'filled') {
    return 'bg-emerald-500/10 text-emerald-200';
  }

  return 'bg-slate-700 text-slate-100';
}
</script>

<template>
  <section class="rounded-xl border border-slate-800 bg-slate-900/70 p-5 shadow">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm uppercase tracking-wide text-brand-300">Orders</p>
        <h2 class="text-xl font-semibold text-white">All statuses</h2>
      </div>
      <span class="text-xs text-slate-500">
        {{ props.refreshing ? 'Refreshing…' : '' }}
      </span>
    </div>

    <div class="mt-4 overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead>
          <tr class="text-slate-400">
            <th class="px-2 py-2 font-medium">Symbol</th>
            <th class="px-2 py-2 font-medium">Side</th>
            <th class="px-2 py-2 font-medium">Price</th>
            <th class="px-2 py-2 font-medium">Amount</th>
            <th class="px-2 py-2 font-medium">Status</th>
            <th class="px-2 py-2 font-medium text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="order in props.orders"
            :key="order.id"
            class="border-t border-slate-800/60 text-slate-100"
          >
            <td class="px-2 py-3 font-semibold">{{ order.symbol }}</td>
            <td
              class="px-2 py-3 font-semibold uppercase"
              :class="order.side === 'buy' ? 'text-emerald-200' : 'text-rose-200'"
            >
              {{ order.side }}
            </td>
            <td class="px-2 py-3">${{ Number(order.price ?? 0).toLocaleString() }}</td>
            <td class="px-2 py-3">{{ Number(order.amount ?? 0).toFixed(4) }}</td>
            <td class="px-2 py-3">
              <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusClasses(order)">
                {{ formatStatus(order) }}
              </span>
            </td>
            <td class="px-2 py-3 text-right">
              <button
                v-if="formatStatus(order) === 'open'"
                class="rounded-lg border border-slate-700 px-3 py-1 text-xs font-semibold text-slate-200 hover:border-brand-400 hover:text-brand-200"
                @click="emit('cancel', order)"
              >
                Cancel
              </button>
            </td>
          </tr>
          <tr v-if="props.orders.length === 0">
            <td colspan="6" class="px-2 py-6 text-center text-slate-500">No orders yet.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
