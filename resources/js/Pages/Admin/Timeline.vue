<template>
  <Head title="Kalender Pendidikan" />

  <AppLayout
    title="Kalender Pendidikan"
    subtitle="Manajemen jadwal akademik, libur, ujian, dan event sekolah"
    :navigation="navigation"
  >
    <!-- Modal Tambah Event -->
    <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div class="bg-slate-800 border border-white/10 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between bg-white/5">
          <h3 class="font-bold text-lg text-white">Tambah Note Kalender</h3>
          <button @click="closeModal" class="text-slate-400 hover:text-white transition-colors">✕</button>
        </div>
        <div class="p-6">
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Kegiatan</label>
              <input v-model="form.title" type="text" class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-white" placeholder="Contoh: Libur Semester Ganjil" />
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Tanggal Mulai</label>
                <input v-model="form.start_date" type="date" class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 text-white" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Tanggal Selesai</label>
                <input v-model="form.end_date" type="date" class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 text-white" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Kategori</label>
                <select v-model="form.type" class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-500 text-white [&>option]:bg-slate-800">
                  <option value="LIBUR">Hari Libur</option>
                  <option value="UJIAN">Ujian / Asesmen</option>
                  <option value="KEGIATAN">Kegiatan Internal</option>
                  <option value="LAINNYA">Lainnya</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Warna Blok</label>
                <div class="flex items-center gap-2 mt-1">
                  <button v-for="color in presetColors" :key="color" @click="form.color = color" 
                    class="w-8 h-8 rounded-full border-2 transition-transform hover:scale-110"
                    :class="form.color === color ? 'border-white' : 'border-transparent'"
                    :style="{ backgroundColor: color }">
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="px-6 py-4 bg-black/20 flex justify-end gap-3 border-t border-white/10">
          <button @click="closeModal" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-300 hover:bg-white/5 transition-colors">Batal</button>
          <button @click="saveEvent" :disabled="isSaving" class="px-6 py-2 rounded-xl text-sm font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition-colors disabled:opacity-50">
            {{ isSaving ? 'Menyimpan...' : 'Simpan Note' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Header Actions -->
    <div class="flex items-center justify-between mb-8">
      <div class="flex items-center gap-4">
        <button @click="changeYear(-1)" class="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-white transition-colors">←</button>
        <h2 class="text-2xl font-black tracking-wide text-transparent bg-clip-text bg-gradient-to-r from-white to-slate-400">
          Tahun Ajaran {{ academicYear }} / {{ academicYear + 1 }}
        </h2>
        <button @click="changeYear(1)" class="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-white transition-colors">→</button>
      </div>
      <button @click="openModal()" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold shadow-lg shadow-indigo-500/20 transition-all flex items-center gap-2">
        <span>+</span> Tambah Manual
      </button>
    </div>

    <!-- 12 Months Grid -->
    <div v-if="!zoomedMonth" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div v-for="(month, mIndex) in months" :key="mIndex" 
           class="bg-slate-800/50 border border-white/5 rounded-2xl overflow-hidden hover:border-indigo-500/30 transition-all cursor-pointer group"
           @click="zoomMonth(month)">
        <div class="px-4 py-3 bg-white/5 flex items-center justify-between border-b border-white/5 group-hover:bg-indigo-500/10 transition-colors">
          <h3 class="font-bold text-sm text-indigo-100 uppercase tracking-widest">{{ month.name }} {{ month.year }}</h3>
          <span class="text-xs text-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity">🔍 Perbesar</span>
        </div>
        <div class="p-4">
          <div class="grid grid-cols-7 gap-1 mb-2">
            <div v-for="day in ['M', 'S', 'S', 'R', 'K', 'J', 'S']" :key="day" class="text-center text-[10px] font-bold text-slate-500">
              {{ day }}
            </div>
          </div>
          <div class="grid grid-cols-7 gap-1">
            <!-- Empty slots -->
            <div v-for="blank in month.firstDayIndex" :key="'b'+blank" class="aspect-square"></div>
            
            <!-- Days -->
            <div v-for="date in month.days" :key="date.fullDate" 
                 class="aspect-square rounded flex items-center justify-center text-xs relative overflow-hidden transition-all"
                 :class="[
                   date.isWeekend ? 'text-rose-400/70' : 'text-slate-300',
                   date.isToday ? 'ring-1 ring-indigo-500 font-bold bg-indigo-500/10' : 'hover:bg-white/10'
                 ]">
              
              <!-- Background Block for Events -->
              <div v-if="date.events.length > 0" class="absolute inset-0 opacity-20 pointer-events-none" :style="{ backgroundColor: date.events[0].color }"></div>
              
              <span class="relative z-10">{{ date.day }}</span>
              
              <!-- Event dot indicator -->
              <div v-if="date.events.length > 0" class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full" :style="{ backgroundColor: date.events[0].color }"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Zoomed Month View -->
    <transition name="fade">
      <div v-if="zoomedMonth" class="bg-slate-800 border border-white/10 rounded-3xl overflow-hidden shadow-2xl relative">
        <div class="px-8 py-6 bg-gradient-to-r from-indigo-900/40 to-slate-800 border-b border-white/10 flex items-center justify-between">
          <div class="flex items-center gap-4">
            <button @click="zoomedMonth = null" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-colors text-xl">←</button>
            <h2 class="text-3xl font-black text-white tracking-wide">{{ zoomedMonth.name }} <span class="text-indigo-400">{{ zoomedMonth.year }}</span></h2>
          </div>
          <div class="text-sm text-slate-400 bg-black/20 px-4 py-2 rounded-lg font-medium">
            💡 Tips: Klik tahan dan seret (drag) pada tanggal untuk mengeblok jadwal.
          </div>
        </div>
        
        <div class="p-8">
          <div class="grid grid-cols-7 gap-4 mb-4">
            <div v-for="day in ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']" :key="day" class="text-center text-xs font-black uppercase tracking-widest text-slate-500">
              {{ day }}
            </div>
          </div>
          
          <div class="grid grid-cols-7 gap-4" @mouseleave="dragEnd">
            <div v-for="blank in zoomedMonth.firstDayIndex" :key="'zb'+blank" class="h-32 rounded-2xl bg-black/10 border border-white/5 opacity-50"></div>
            
            <div v-for="date in zoomedMonth.days" :key="'z'+date.fullDate" 
                 @mousedown="dragStart(date)"
                 @mouseenter="dragOver(date)"
                 @mouseup="dragEnd"
                 class="h-32 rounded-2xl border transition-all cursor-pointer relative flex flex-col p-2 select-none group"
                 :class="[
                   isDateInSelection(date) ? 'bg-indigo-500/20 border-indigo-500/50 shadow-[0_0_15px_rgba(99,102,241,0.2)]' : 'bg-slate-800/50 border-white/5 hover:border-white/20',
                   date.isWeekend ? 'bg-rose-900/10' : ''
                 ]">
              
              <div class="flex justify-between items-start mb-2">
                <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors"
                      :class="date.isToday ? 'bg-indigo-500 text-white' : (date.isWeekend ? 'text-rose-400' : 'text-slate-300 group-hover:text-white')">
                  {{ date.day }}
                </span>
                <button v-if="date.events.length === 0" @click.stop="openModal(date.fullDate, date.fullDate)" class="opacity-0 group-hover:opacity-100 p-1 text-slate-400 hover:text-white transition-opacity">+</button>
              </div>

              <!-- Event Pills -->
              <div class="flex-1 overflow-y-auto space-y-1 no-scrollbar">
                <div v-for="event in date.events" :key="event.id" 
                     @click.stop="deleteEvent(event)"
                     class="text-[10px] font-bold px-2 py-1.5 rounded-lg truncate transition-transform hover:scale-95 cursor-not-allowed group/event relative"
                     :style="{ backgroundColor: event.color + '30', color: event.color, border: '1px solid ' + event.color + '50' }">
                  {{ event.title }}
                  <div class="absolute inset-0 bg-red-500 flex items-center justify-center text-white opacity-0 group-hover/event:opacity-100 transition-opacity rounded-lg">
                    Hapus
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  calendars: Array
});

// Navigation map
const navigation = [
  { label: 'Utama', items: [{ href: '/admin/dashboard', icon: '📊', label: 'Dashboard' }] },
  { label: 'Sekolah', items: [
      { href: '/admin/wali-kelas', icon: '👤', label: 'Wali Kelas' },
      { href: '/admin/siswa', icon: '👥', label: 'Data Siswa' },
      { href: '/admin/events', icon: '🗓️', label: 'Event & Libur' },
      { href: '/admin/kuesioner', icon: '⭐', label: 'Kuesioner Kinerja' },
  ]},
  { label: 'Konfigurasi', items: [
      { href: '/admin/jadwal-jp', icon: '⏰', label: 'Jadwal JP' },
      { href: '/admin/grade-config', icon: '⚖️', label: 'Bobot Nilai' },
      { href: '/admin/timeline', icon: '📅', label: 'Timeline (Kalender)' },
  ]},
  { label: 'Laporan', items: [
      { href: '/admin/monitoring-nilai', icon: '📈', label: 'Monitoring Nilai' },
      { href: '/admin/rapor', icon: '📋', label: 'Ekspor Rapor' },
      { href: '/admin/laporan-performa', icon: '🏆', label: 'Laporan Performa' },
      { href: '/admin/jurnal', icon: '📓', label: 'Jurnal Tatap Muka' },
  ]}
];

const presetColors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899'];

const academicYear = ref(2025);
const changeYear = (delta) => academicYear.value += delta;

// Month Generation
const monthNames = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];

const getEventsForDate = (fullDateStr) => {
  if (!props.calendars) return [];
  const dateObj = new Date(fullDateStr);
  return props.calendars.filter(cal => {
    const start = new Date(cal.start_date);
    const end = new Date(cal.end_date);
    // Remove time portion for comparison
    start.setHours(0,0,0,0);
    end.setHours(0,0,0,0);
    dateObj.setHours(0,0,0,0);
    return dateObj >= start && dateObj <= end;
  });
};

const months = computed(() => {
  const result = [];
  const todayStr = new Date().toISOString().split('T')[0];

  for (let i = 0; i < 12; i++) {
    // 0 = Juli (academicYear), 6 = Januari (academicYear + 1)
    const monthIndex = (i + 6) % 12; 
    const year = i < 6 ? academicYear.value : academicYear.value + 1;
    
    const daysInMonth = new Date(year, monthIndex + 1, 0).getDate();
    const firstDayIndex = new Date(year, monthIndex, 1).getDay(); // 0 = Sunday

    const days = [];
    for (let d = 1; d <= daysInMonth; d++) {
      const fullDate = `${year}-${String(monthIndex + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
      const dayOfWeek = new Date(year, monthIndex, d).getDay();
      days.push({
        day: d,
        fullDate: fullDate,
        isWeekend: dayOfWeek === 0, // Hanya minggu yg merah bawaan
        isToday: fullDate === todayStr,
        events: getEventsForDate(fullDate)
      });
    }

    result.push({
      name: monthNames[i],
      year: year,
      firstDayIndex: firstDayIndex,
      days: days
    });
  }
  return result;
});

const zoomedMonth = ref(null);
const zoomMonth = (month) => { zoomedMonth.value = month; };

// Drag Selection Logic
const isDragging = ref(false);
const dragStartObj = ref(null);
const dragCurrentObj = ref(null);

const dragStart = (date) => {
  isDragging.value = true;
  dragStartObj.value = date;
  dragCurrentObj.value = date;
};

const dragOver = (date) => {
  if (isDragging.value) {
    dragCurrentObj.value = date;
  }
};

const dragEnd = () => {
  if (isDragging.value && dragStartObj.value && dragCurrentObj.value) {
    const d1 = dragStartObj.value.fullDate;
    const d2 = dragCurrentObj.value.fullDate;
    const start = d1 < d2 ? d1 : d2;
    const end = d1 > d2 ? d1 : d2;
    
    openModal(start, end);
  }
  isDragging.value = false;
  dragStartObj.value = null;
  dragCurrentObj.value = null;
};

const isDateInSelection = (date) => {
  if (!isDragging.value || !dragStartObj.value || !dragCurrentObj.value) return false;
  const target = date.fullDate;
  const d1 = dragStartObj.value.fullDate;
  const d2 = dragCurrentObj.value.fullDate;
  const start = d1 < d2 ? d1 : d2;
  const end = d1 > d2 ? d1 : d2;
  return target >= start && target <= end;
};

// Modal & Form Logic
const showModal = ref(false);
const isSaving = ref(false);
const form = ref({
  title: '', start_date: '', end_date: '', type: 'LIBUR', color: '#ef4444'
});

const openModal = (start = '', end = '') => {
  form.value = {
    title: '',
    start_date: start,
    end_date: end || start,
    type: 'LIBUR',
    color: '#ef4444'
  };
  showModal.value = true;
};

const closeModal = () => { showModal.value = false; };

const saveEvent = () => {
  if (!form.value.title || !form.value.start_date || !form.value.end_date) return;
  isSaving.value = true;
  router.post('/admin/timeline', form.value, {
    preserveScroll: true,
    onSuccess: () => {
      isSaving.value = false;
      showModal.value = false;
    },
    onError: () => isSaving.value = false
  });
};

const deleteEvent = (event) => {
  if(confirm(`Hapus catatan "${event.title}"?`)) {
    router.delete(`/admin/timeline/${event.id}`, { preserveScroll: true });
  }
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
