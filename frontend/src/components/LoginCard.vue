<script setup>
import { computed, reactive, ref } from 'vue';

const props = defineProps({
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
});

const emit = defineEmits(['submit']);

const mode = ref('login');
const form = reactive({
  name: '',
  email: '',
  password: '',
});

const title = computed(() =>
  mode.value === 'login' ? 'Sign in to trade' : 'Create an account',
);

function toggleMode() {
  mode.value = mode.value === 'login' ? 'register' : 'login';
}

function handleSubmit() {
  emit('submit', {
    mode: mode.value,
    payload: {
      name: form.name,
      email: form.email,
      password: form.password,
    },
  });
}
</script>

<template>
  <section class="w-full max-w-md rounded-xl border border-slate-700 bg-slate-900/80 p-6 shadow-xl">
    <p class="text-sm uppercase tracking-wide text-brand-300">Crypto Exchange</p>
    <h2 class="mt-2 text-2xl font-semibold text-white">{{ title }}</h2>

    <form class="mt-6 space-y-4" @submit.prevent="handleSubmit">
      <div v-if="mode === 'register'">
        <label class="block text-sm font-medium text-slate-300">
          Full name
          <input
            v-model="form.name"
            type="text"
            required
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-white focus:border-brand-400 focus:outline-none"
            placeholder="Jane Doe"
          />
        </label>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-300">
          Email
          <input
            v-model="form.email"
            type="email"
            required
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-white focus:border-brand-400 focus:outline-none"
            placeholder="you@example.com"
          />
        </label>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-300">
          Password
          <input
            v-model="form.password"
            type="password"
            minlength="8"
            required
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-white focus:border-brand-400 focus:outline-none"
            placeholder="••••••••"
          />
        </label>
      </div>

      <p v-if="props.error" class="rounded-md bg-red-500/10 px-3 py-2 text-sm text-red-200">
        {{ props.error }}
      </p>

      <button
        type="submit"
        :disabled="props.loading"
        class="w-full rounded-lg bg-brand-500 px-4 py-2 font-semibold text-white transition hover:bg-brand-400 disabled:cursor-not-allowed disabled:opacity-70"
      >
        {{ props.loading ? 'Working...' : mode === 'login' ? 'Sign in' : 'Create account' }}
      </button>
    </form>

    <p class="mt-4 text-center text-sm text-slate-400">
      <span v-if="mode === 'login'">Need an account?</span>
      <span v-else>Already trading here?</span>
      <button
        type="button"
        class="ml-2 font-semibold text-brand-300 hover:text-brand-200"
        @click="toggleMode"
      >
        {{ mode === 'login' ? 'Register now' : 'Back to login' }}
      </button>
    </p>
  </section>
</template>
