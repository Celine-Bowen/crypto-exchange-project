<script setup>
import { computed, reactive, watch } from 'vue';

const props = defineProps({
  symbols: { type: Array, default: () => [] },
  symbol: { type: String, default: 'BTC' },
  placing: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'update:symbol']);

const form = reactive({
  symbol: props.symbol,
  side: 'buy',
  price: '',
  amount: '',
});

watch(
  () => props.symbol,
  (value) => {
    form.symbol = value;
  },
);

const total = computed(() => {
  const price = parseFloat(form.price);
  const amount = parseFloat(form.amount);

  if (Number.isNaN(price) || Number.isNaN(amount)) {
    return '0.00';
  }

  return (price * amount).toFixed(2);
});

const canSubmit = computed(() => {
  return Number(form.price) > 0 && Number(form.amount) > 0;
});

function submitOrder() {
  if (!canSubmit.value) {
    return;
  }

  emit('submit', {
    symbol: form.symbol,
    side: form.side,
    price: Number(form.price),
    amount: Number(form.amount),
  });

  form.amount = '';
  form.price = '';
}
</script>

<template>
  <section class="rounded-xl border border-slate-800 bg-slate-900/70 p-5 shadow">
    <header class="flex items-center justify-between">
      <div>
        <p class="text-sm uppercase tracking-wide text-brand-300">Create Order</p>
        <h2 class="text-xl font-semibold text-white">Limit order</h2>
      </div>
      <select
        v-model="form.symbol"
        class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm font-medium text-white focus:border-brand-400 focus:outline-none"
        @change="emit('update:symbol', form.symbol)"
      >
        <option v-for="symbolOption in props.symbols" :key="symbolOption" :value="symbolOption">
          {{ symbolOption }}
        </option>
      </select>
    </header>

    <form class="mt-5 space-y-4" @submit.prevent="submitOrder">
      <div class="grid grid-cols-2 gap-3">
        <button
          type="button"
          :class="[
            'rounded-lg px-3 py-2 text-sm font-semibold transition',
            form.side === 'buy'
              ? 'bg-emerald-600 text-white'
              : 'bg-slate-800 text-slate-200 hover:bg-slate-700',
          ]"
          @click="form.side = 'buy'"
        >
          Buy
        </button>
        <button
          type="button"
          :class="[
            'rounded-lg px-3 py-2 text-sm font-semibold transition',
            form.side === 'sell'
              ? 'bg-rose-600 text-white'
              : 'bg-slate-800 text-slate-200 hover:bg-slate-700',
          ]"
          @click="form.side = 'sell'"
        >
          Sell
        </button>
      </div>

      <label class="block text-sm font-medium text-slate-300">
        Price (USD)
        <input
          v-model="form.price"
          type="number"
          step="0.01"
          min="0"
          required
          class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-white focus:border-brand-400 focus:outline-none"
          placeholder="95000"
        />
      </label>

      <label class="block text-sm font-medium text-slate-300">
        Amount ({{ form.symbol }})
        <input
          v-model="form.amount"
          type="number"
          min="0"
          step="0.00000001"
          required
          class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-white focus:border-brand-400 focus:outline-none"
          placeholder="0.25"
        />
      </label>

      <div class="rounded-lg border border-slate-800 bg-slate-950/50 px-4 py-3 text-sm text-slate-300">
        <p>
          <span class="text-slate-400">Estimated notional:</span>
          <span class="ml-2 font-semibold text-white">${{ total }}</span>
        </p>
        <p class="text-xs text-slate-500">
          Includes the 1.5% commission locked upfront for buy orders.
        </p>
      </div>

      <button
        type="submit"
        :disabled="!canSubmit || props.placing"
        class="w-full rounded-lg bg-brand-500 px-4 py-2 font-semibold text-white transition hover:bg-brand-400 disabled:cursor-not-allowed disabled:opacity-70"
      >
        {{ props.placing ? 'Placing...' : form.side === 'buy' ? 'Place buy order' : 'Place sell order' }}
      </button>
    </form>
  </section>
</template>
