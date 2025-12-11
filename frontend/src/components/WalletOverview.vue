<script setup>
import { computed } from 'vue';

const props = defineProps({
  profile: { type: Object, default: null },
});

const assets = computed(() => props.profile?.assets ?? []);

function formatUsd(value) {
  return Number(value ?? 0).toLocaleString('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  });
}
</script>

<template>
  <section class="rounded-xl border border-slate-800 bg-slate-900/70 p-5 shadow">
    <p class="text-sm uppercase tracking-wide text-brand-300">Wallet overview</p>
    <div class="mt-2 flex items-baseline gap-2">
      <h2 class="text-3xl font-semibold text-white">
        {{ formatUsd(profile?.balance ?? 0) }}
      </h2>
      <span class="text-sm text-slate-400">Available USD</span>
    </div>

    <div class="mt-5 space-y-3">
      <div
        v-for="asset in assets"
        :key="asset.symbol"
        class="flex items-center justify-between rounded-lg border border-slate-800/80 bg-slate-950/40 px-4 py-3"
      >
        <div>
          <p class="text-base font-semibold text-white">{{ asset.symbol }}</p>
          <p class="text-xs text-slate-400">Locked: {{ Number(asset.locked_amount ?? 0).toFixed(4) }}</p>
        </div>
        <p class="text-lg font-semibold text-brand-200">
          {{ Number(asset.amount ?? 0).toFixed(4) }}
        </p>
      </div>
      <p v-if="assets.length === 0" class="text-sm text-slate-500">
        No crypto holdings yet. Place an order to acquire your first asset.
      </p>
    </div>
  </section>
</template>
