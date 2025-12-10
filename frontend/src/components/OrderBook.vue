<script setup>
const props = defineProps({
  book: {
    type: Object,
    default: () => ({ buy: [], sell: [], symbol: '' }),
  },
  symbols: { type: Array, default: () => [] },
  symbol: { type: String, default: 'BTC' },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:symbol']);

const displayRows = 7;
</script>

<template>
  <section class="rounded-xl border border-slate-800 bg-slate-900/70 p-5 shadow">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <p class="text-sm uppercase tracking-wide text-brand-300">Live Orderbook</p>
        <h2 class="text-xl font-semibold text-white">Symbol: {{ props.symbol }}</h2>
      </div>
      <select
        class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm font-medium text-white focus:border-brand-400 focus:outline-none"
        :value="props.symbol"
        @change="emit('update:symbol', $event.target.value)"
      >
        <option v-for="symbolOption in props.symbols" :key="symbolOption" :value="symbolOption">
          {{ symbolOption }}
        </option>
      </select>
    </div>

    <div class="mt-4 grid gap-6 md:grid-cols-2">
      <div>
        <h3 class="text-sm font-semibold uppercase text-emerald-300">Buy orders</h3>
        <div class="mt-2 overflow-hidden rounded-lg border border-slate-800/60">
          <table class="w-full text-sm">
            <thead class="bg-slate-950/40 text-slate-400">
              <tr>
                <th class="px-3 py-2 text-left font-medium">Price (USD)</th>
                <th class="px-3 py-2 text-left font-medium">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="order in props.book.buy?.slice(0, displayRows)"
                :key="order.id"
                class="border-t border-slate-800/40 text-emerald-100"
              >
                <td class="px-3 py-2 font-semibold">${{ Number(order.price ?? 0).toLocaleString() }}</td>
                <td class="px-3 py-2">{{ Number(order.amount ?? 0).toFixed(4) }}</td>
              </tr>
              <tr v-if="props.book.buy?.length === 0">
                <td colspan="2" class="px-3 py-4 text-center text-slate-500">
                  {{ props.loading ? 'Loading book…' : 'No open buy orders.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <h3 class="text-sm font-semibold uppercase text-rose-300">Sell orders</h3>
        <div class="mt-2 overflow-hidden rounded-lg border border-slate-800/60">
          <table class="w-full text-sm">
            <thead class="bg-slate-950/40 text-slate-400">
              <tr>
                <th class="px-3 py-2 text-left font-medium">Price (USD)</th>
                <th class="px-3 py-2 text-left font-medium">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="order in props.book.sell?.slice(0, displayRows)"
                :key="order.id"
                class="border-t border-slate-800/40 text-rose-100"
              >
                <td class="px-3 py-2 font-semibold">${{ Number(order.price ?? 0).toLocaleString() }}</td>
                <td class="px-3 py-2">{{ Number(order.amount ?? 0).toFixed(4) }}</td>
              </tr>
              <tr v-if="props.book.sell?.length === 0">
                <td colspan="2" class="px-3 py-4 text-center text-slate-500">
                  {{ props.loading ? 'Loading book…' : 'No open sell orders.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</template>
