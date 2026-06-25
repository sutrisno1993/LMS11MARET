<template>
  <!-- Mobile Sidebar Backdrop Overlay -->
  <div 
    v-if="mobileSidebarOpen" 
    @click="mobileSidebarOpen = false" 
    class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 md:hidden"
  ></div>

  <!-- Sidebar -->
  <aside 
    :class="[
      'fixed top-0 left-0 bottom-0 bg-[#111827] border-r border-white/8 flex flex-col z-50 transition-all duration-300',
      isMinimized ? 'md:w-20' : 'md:w-64',
      mobileSidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full md:translate-x-0'
    ]"
  >
    <!-- Logo & Minimize Toggle -->
    <div v-if="showDetails" class="flex items-center justify-between px-5 py-6 border-b border-white/8">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-xl shadow-lg shadow-indigo-500/30 flex-shrink-0">
          🎓
        </div>
        <div>
          <div class="text-sm font-bold text-white">LMS 11 Maret</div>
          <div class="text-xs text-slate-500">{{ roleLabel }}</div>
        </div>
      </div>
      <button @click="toggleSidebar" class="hidden md:flex p-1.5 rounded bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white transition-colors" title="Minimize Sidebar">
        <span class="text-xs">◀</span>
      </button>
    </div>
    <div v-else class="flex flex-col items-center gap-3 py-6 border-b border-white/8">
      <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-xl shadow-lg shadow-indigo-500/30 flex-shrink-0">
        🎓
      </div>
      <button @click="toggleSidebar" class="hidden md:flex p-1.5 rounded bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white transition-colors" title="Maximize Sidebar">
        <span class="text-xs">▶</span>
      </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
      <!-- Mobile-only status widgets -->
      <div class="flex flex-col gap-2.5 px-2 pb-4 mb-4 border-b border-white/8 md:hidden">
        <!-- Current JP Widget -->
        <div class="flex items-center gap-2.5 bg-emerald-500/10 border border-emerald-500/20 px-3 py-2 rounded-xl text-emerald-400">
          <span class="text-base text-emerald-400">🗓️</span>
          <div class="flex flex-col justify-center">
            <span class="text-[9px] font-bold text-emerald-600 uppercase tracking-widest leading-none mb-0.5">Status Saat Ini</span>
            <span class="text-xs font-bold leading-none tracking-tight">{{ currentJpText }}</span>
          </div>
        </div>

        <!-- Jam Server Widget -->
        <div class="flex items-center gap-2.5 bg-[#1E293B]/80 border border-white/10 px-3 py-2 rounded-xl">
          <span class="text-base text-indigo-400 animate-pulse">⌚</span>
          <div class="flex flex-col justify-center">
            <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest leading-none mb-0.5">Jam Server</span>
            <span class="text-sm font-mono font-bold text-white leading-none tracking-tight">{{ serverTimeString }}</span>
          </div>
        </div>
      </div>

      <template v-for="section in navigation" :key="section.label">
        <div v-if="showDetails" class="px-2 pt-4 pb-1.5 text-[10px] font-semibold text-slate-600 uppercase tracking-widest">
          {{ section.label }}
        </div>
        <Link
          v-for="item in section.items"
          :key="item.href"
          :href="item.href"
          :class="['nav-link', { active: $page.url.startsWith(item.href) }, { 'justify-center': !showDetails }]"
          :title="!showDetails ? item.label : ''"
        >
          <span class="text-base w-5 text-center">{{ item.icon }}</span>
          <span v-if="showDetails">{{ item.label }}</span>
          <span v-if="item.badge && showDetails" class="ml-auto bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">
            {{ item.badge }}
          </span>
        </Link>
      </template>
    </nav>

    <!-- User footer -->
    <div class="p-3 border-t border-white/8 relative group">
      <div 
        :class="[
          'flex items-center rounded-xl hover:bg-white/5 cursor-pointer transition-colors',
          !showDetails ? 'justify-center p-1.5' : 'gap-2.5 p-2.5'
        ]"
      >
        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center font-bold text-sm flex-shrink-0">
          {{ userInitial }}
        </div>
        <div v-if="showDetails" class="min-w-0">
          <div class="text-sm font-semibold text-white truncate">{{ $page.props.auth?.user?.name || 'Guest User' }}</div>
          <div class="text-xs text-slate-500">{{ roleLabel }}</div>
        </div>
        <button v-if="showDetails" class="ml-auto text-slate-600 group-hover:text-slate-400 text-lg transition-colors">⋯</button>
      </div>
      
      <!-- Logout Dropdown -->
      <div class="absolute bottom-full left-0 pb-2 w-full px-3 opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition-all">
        <Link href="/logout" method="post" as="button" class="w-full text-center px-4 py-2.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs font-bold rounded-xl border border-red-500/20 transition-colors">
          <span v-if="showDetails">🚪 Keluar (Logout)</span>
          <span v-else>🚪</span>
        </Link>
      </div>
    </div>
  </aside>

  <!-- Main content area -->
  <div 
    :class="[
      'flex flex-col min-h-screen transition-all duration-300 ml-0',
      isMinimized ? 'md:ml-20' : 'md:ml-64'
    ]"
  >
    <!-- Topbar -->
    <header class="sticky top-0 z-40 h-16 bg-[#0B0F1A]/80 backdrop-blur-md border-b border-white/8 flex items-center px-4 md:px-7 gap-4">
      <!-- Hamburger menu toggle -->
      <button 
        @click="mobileSidebarOpen = !mobileSidebarOpen" 
        class="md:hidden p-2 rounded-lg border border-white/10 text-slate-400 hover:text-white hover:bg-white/5 transition-colors"
        title="Toggle Menu"
      >
        ☰
      </button>
      <div class="min-w-0 flex-1 md:flex-initial">
        <h1 class="text-sm md:text-base font-bold text-white truncate">{{ title }}</h1>
        <p class="text-[10px] md:text-xs text-slate-500 truncate">{{ subtitle }}</p>
      </div>
      <div class="ml-auto flex items-center gap-2 md:gap-3">
        <!-- Current JP Widget -->
        <div class="hidden md:flex items-center gap-2.5 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1.5 rounded-xl shadow-inner text-emerald-400">
          <span class="text-base text-emerald-400">🗓️</span>
          <div class="flex flex-col justify-center">
            <span class="text-[9px] font-bold text-emerald-600 uppercase tracking-widest leading-none mb-0.5">Status Saat Ini</span>
            <span class="text-xs font-bold leading-none tracking-tight">{{ currentJpText }}</span>
          </div>
        </div>

        <!-- Jam Server Widget -->
        <div class="hidden md:flex items-center gap-2.5 bg-[#1E293B]/80 border border-white/10 px-3 py-1.5 rounded-xl shadow-inner mr-2">
          <span class="text-base text-indigo-400" :class="{'animate-pulse': true}">⌚</span>
          <div class="flex flex-col justify-center">
            <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest leading-none mb-0.5">Jam Server</span>
            <span class="text-sm font-mono font-bold text-white leading-none tracking-tight">{{ serverTimeString }}</span>
          </div>
        </div>

        <slot name="topbar-actions" />
        <div class="hidden sm:flex items-center gap-1.5 bg-green-500/10 border border-green-500/20 px-3 py-1.5 rounded-full text-xs font-semibold text-green-400">
          <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
          Live
        </div>
        <button class="hidden sm:flex relative w-9 h-9 rounded-lg border border-white/10 flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/5 transition-colors">
          🔔
          <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-red-500 rounded-full"></span>
        </button>
      </div>
    </header>

    <!-- Page content -->
    <main class="flex-1 p-7 relative">
      <slot />
      
      <!-- Development Time Widget -->
      <div v-if="$page.props.app?.is_local_env" class="fixed bottom-6 right-6 z-50">
        <div class="bg-[#1E293B] border border-white/10 shadow-2xl rounded-2xl p-4 w-72 backdrop-blur-md bg-opacity-95">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold text-white flex items-center gap-2">
              <span>⏱️</span> Dev Time Control
            </h3>
            <span v-if="$page.props.app.is_mock_time" class="bg-indigo-500/20 text-indigo-400 text-[10px] px-2 py-0.5 rounded-full font-bold">MOCKED</span>
          </div>
          <p class="text-[11px] text-slate-400 mb-3 font-mono">{{ $page.props.app.current_time }}</p>
          <form @submit.prevent="updateMockTime" class="space-y-2">
            <input v-model="mockTimeForm.mock_time" type="datetime-local" step="1" class="w-full bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 text-xs text-white" required>
            <div class="flex gap-2">
              <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-semibold py-1.5 rounded-lg transition-colors">Set Waktu</button>
              <button type="button" @click="resetMockTime" v-if="$page.props.app.is_mock_time" class="flex-1 bg-red-500/20 hover:bg-red-500/30 text-red-400 text-[11px] font-semibold py-1.5 rounded-lg border border-red-500/30 transition-colors">Reset</button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { Link, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  title: { type: String, default: 'Dashboard' },
  subtitle: { type: String, default: '' },
  navigation: { type: Array, default: () => [] },
});

const page = usePage();

const isMinimized = ref(false);
const mobileSidebarOpen = ref(false);
const showDetails = computed(() => {
  return !isMinimized.value || mobileSidebarOpen.value;
});
const serverTime = ref(new Date());
let timer = null;

// Close sidebar on mobile when navigating
watch(() => page.url, () => {
  mobileSidebarOpen.value = false;
});

onMounted(() => {
  if (page.props.app?.current_time) {
    serverTime.value = new Date(page.props.app.current_time);
  }
  
  timer = setInterval(() => {
    serverTime.value = new Date(serverTime.value.getTime() + 1000);
  }, 1000);

  // Load minimized state from storage
  const stored = localStorage.getItem('sidebar-minimized');
  if (stored) {
    isMinimized.value = stored === 'true';
  }
});

const toggleSidebar = () => {
  isMinimized.value = !isMinimized.value;
  localStorage.setItem('sidebar-minimized', isMinimized.value);
};

onUnmounted(() => {
  if (timer) clearInterval(timer);
});

const serverTimeString = computed(() => {
  return serverTime.value.toLocaleTimeString('id-ID', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' }).replace(/\./g, ':');
});

const currentJpText = computed(() => {
  const schedules = page.props.app?.today_schedules;
  if (!schedules || schedules.length === 0) return 'Tidak ada KBM';
  
  const now = serverTime.value;
  const currentMinutes = now.getHours() * 60 + now.getMinutes();

  const activeSchedules = schedules.filter(s => {
    if (!s.waktu_mulai || !s.waktu_selesai) return false;
    const [hStart, mStart] = s.waktu_mulai.split(':');
    const [hEnd, mEnd] = s.waktu_selesai.split(':');
    const startMins = parseInt(hStart) * 60 + parseInt(mStart);
    const endMins = parseInt(hEnd) * 60 + parseInt(mEnd);
    return currentMinutes >= startMins && currentMinutes < endMins;
  });

  if (activeSchedules.length > 0) {
    return activeSchedules.map(s => {
      let title = s.is_kbm ? `Jam ke-${s.jam_ke}` : s.keterangan;
      return `${title} (${s.shift === 'PAGI' ? 'Pagi' : 'Siang'})`;
    }).join(' • ');
  }
  
  return 'Sedang Tidak Ada KBM';
});

const roleLabel = computed(() => {
  const role = page.props.auth?.user?.role;
  const map = { ADMIN: 'Admin Kurikulum', TEACHER: 'Guru', STUDENT: 'Siswa' };
  return map[role] || 'Pengguna';
});

const userInitial = computed(() => {
  const name = page.props.auth?.user?.name || 'U';
  return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
});

const mockTimeForm = useForm({
  mock_time: ''
});

const updateMockTime = () => {
  mockTimeForm.post('/dev/mock-time', {
    preserveScroll: true,
  });
};

const resetMockTime = () => {
  useForm({}).post('/dev/reset-time', {
    preserveScroll: true,
  });
};
</script>
