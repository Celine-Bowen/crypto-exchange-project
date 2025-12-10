<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import LoginCard from './components/LoginCard.vue';
import OrderBook from './components/OrderBook.vue';
import OrderForm from './components/OrderForm.vue';
import OrdersTable from './components/OrdersTable.vue';
import WalletOverview from './components/WalletOverview.vue';
import api, { clearAuthToken, setAuthToken } from './services/api';
import { disconnectRealtime, initRealtime } from './services/realtime';

const symbols = ['BTC', 'ETH'];

const selectedSymbol = ref(symbols[0]);
const profile = ref(null);
const orders = ref([]);
const orderbook = ref({ symbol: symbols[0], buy: [], sell: [] });
const authLoading = ref(false);
const authError = ref('');
const placingOrder = ref(false);
const refreshingOrders = ref(false);
const loadingBook = ref(false);
const bootstrapLoading = ref(false);
const flash = ref('');
const token = ref(localStorage.getItem('cex_token'));

if (token.value) {
  setAuthToken(token.value);
}

const isAuthenticated = computed(() => Boolean(token.value));

watch(selectedSymbol, () => {
  if (isAuthenticated.value) {
    fetchOrderbook();
  }
});

onMounted(() => {
  if (token.value) {
    bootstrap();
  }
});

function setFlash(message) {
  flash.value = message;

  if (message) {
    setTimeout(() => {
      if (flash.value === message) {
        flash.value = '';
      }
    }, 4500);
  }
}

async function bootstrap() {
  bootstrapLoading.value = true;
  try {
    await fetchProfile();
    initRealtimeChannel();
    await Promise.all([fetchOrders(), fetchOrderbook()]);
  } catch (error) {
    handleAuthFailure(error);
  } finally {
    bootstrapLoading.value = false;
  }
}

async function handleAuth({ mode, payload }) {
  authLoading.value = true;
  authError.value = '';

  try {
    const endpoint = mode === 'register' ? '/register' : '/login';
    const { data } = await api.post(endpoint, payload);
    const profileData = data.profile?.data ?? data.profile ?? data.data ?? null;

    token.value = data.token;
    localStorage.setItem('cex_token', data.token);
    setAuthToken(data.token);
    profile.value = profileData;
    setFlash(mode === 'register' ? 'Account created with demo USD funds.' : 'Welcome back!');

    await bootstrap();
  } catch (error) {
    authError.value = parseError(error);
  } finally {
    authLoading.value = false;
  }
}

async function fetchProfile() {
  const { data } = await api.get('/profile');
  profile.value = data.data ?? data;
  return profile.value;
}

async function fetchOrders() {
  refreshingOrders.value = true;
  try {
    const { data } = await api.get('/orders', { params: { mine: true } });
    orders.value = data.orders?.data ?? data.orders ?? data.data ?? [];
  } finally {
    refreshingOrders.value = false;
  }
}

async function fetchOrderbook() {
  loadingBook.value = true;
  try {
    const { data } = await api.get('/orders', {
      params: { symbol: selectedSymbol.value },
    });

    orderbook.value = {
      symbol: data.symbol ?? selectedSymbol.value,
      buy: data.buy?.data ?? data.buy ?? [],
      sell: data.sell?.data ?? data.sell ?? [],
    };
  } finally {
    loadingBook.value = false;
  }
}

async function handleOrderSubmit(payload) {
  placingOrder.value = true;
  try {
    const { data } = await api.post('/orders', payload);
    const order = data.data ?? data;
    orders.value = [order, ...orders.value];
    setFlash('Order placed successfully.');
    await Promise.all([fetchProfile(), fetchOrderbook()]);
  } catch (error) {
    setFlash(parseError(error));
  } finally {
    placingOrder.value = false;
  }
}

async function cancelOrder(order) {
  try {
    await api.post(`/orders/${order.id}/cancel`);
    setFlash('Order cancelled.');
    await Promise.all([fetchProfile(), fetchOrders(), fetchOrderbook()]);
  } catch (error) {
    setFlash(parseError(error));
  }
}

async function handleLogout() {
  try {
    await api.post('/logout');
  } catch {
    // Silently ignore since token might already be invalid.
  }

  token.value = null;
  profile.value = null;
  orders.value = [];
  orderbook.value = { symbol: selectedSymbol.value, buy: [], sell: [] };
  localStorage.removeItem('cex_token');
  clearAuthToken();
  disconnectRealtime();
}

function initRealtimeChannel() {
  if (!profile.value?.id || !token.value) {
    return;
  }

  initRealtime(token.value, profile.value.id, handleRealtimeEvent);
}

function handleRealtimeEvent(payload) {
  if (!isAuthenticated.value) {
    return;
  }

  const profileMap = payload.profiles ?? {};
  const userId = profile.value?.id;
  const profileUpdate = profileMap[userId] ?? profileMap[String(userId)];

  if (profileUpdate) {
    profile.value = profileUpdate;
  }

  const updates = [payload.buy_order, payload.sell_order].filter(Boolean);

  if (updates.length > 0) {
    const current = [...orders.value];

    updates.forEach((update) => {
      const idx = current.findIndex((order) => order.id === update.id);
      if (idx >= 0) {
        current[idx] = update;
      } else if (update.user_id === userId) {
        current.unshift(update);
      }
    });

    orders.value = current;
    setFlash('A new trade just matched!');
  }

  fetchOrderbook();
}

function handleAuthFailure(error) {
  handleLogout();
  authError.value = parseError(error);
}

function parseError(error) {
  if (error.response?.data?.message) {
    return error.response.data.message;
  }

  if (error.response?.data?.errors) {
    const firstError = Object.values(error.response.data.errors)[0];
    if (Array.isArray(firstError)) {
      return firstError[0];
    }
  }

  return 'Unexpected error. Please try again.';
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <header class="border-b border-slate-900 bg-slate-950/80">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
        <div>
          <p class="text-xs uppercase tracking-[0.3em] text-brand-300">Cypress Exchange</p>
          <h1 class="text-lg font-semibold text-white">Demo crypto matching engine</h1>
        </div>

        <div v-if="isAuthenticated" class="flex items-center gap-4">
          <div class="text-right">
            <p class="text-xs text-slate-400">Signed in as</p>
            <p class="text-sm font-semibold text-white">{{ profile?.name }}</p>
          </div>
          <button
            class="rounded-lg border border-slate-700 px-3 py-1 text-sm font-semibold hover:border-brand-400 hover:text-brand-200"
            @click="handleLogout"
          >
            Logout
          </button>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-6xl space-y-6 px-4 pb-12 pt-6">
      <transition name="fade">
        <div
          v-if="flash"
          class="rounded-lg border border-brand-500/40 bg-brand-500/10 px-4 py-3 text-sm text-brand-100"
        >
          {{ flash }}
        </div>
      </transition>

      <LoginCard
        v-if="!isAuthenticated"
        :loading="authLoading"
        :error="authError"
        @submit="handleAuth"
      />

      <div v-else class="space-y-6">
        <p v-if="bootstrapLoading" class="text-sm text-slate-400">Syncing your wallet...</p>

        <div class="grid gap-6 lg:grid-cols-2">
          <OrderForm
            :symbols="symbols"
            :symbol="selectedSymbol"
            :placing="placingOrder"
            @submit="handleOrderSubmit"
            @update:symbol="selectedSymbol = $event"
          />
          <WalletOverview :profile="profile" />
        </div>

        <OrderBook
          :book="orderbook"
          :symbols="symbols"
          :symbol="selectedSymbol"
          :loading="loadingBook"
          @update:symbol="selectedSymbol = $event"
        />

        <OrdersTable
          :orders="orders"
          :refreshing="refreshingOrders"
          @cancel="cancelOrder"
        />
      </div>
    </main>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.4s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
