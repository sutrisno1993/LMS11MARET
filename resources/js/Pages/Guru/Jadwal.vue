<template>
  <Head title="Jadwal Mengajar Mingguan" />

  <AppLayout
    title="Jadwal Mengajar Mingguan"
    subtitle="Grid Jadwal Guru"
    :navigation="navigation"
  >
    <template #topbar-actions>
      <!-- Indicator current shift -->
      <div class="flex items-center gap-1.5 bg-indigo-500/10 border border-indigo-500/20 px-3 py-1.5 rounded-full text-xs font-semibold text-indigo-400">
        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
        ● {{ activeShift === 'PAGI' ? 'Pagi' : 'Siang' }}
      </div>
    </template>

    <!-- SHIFT SELECTOR TABS -->
    <div class="flex items-center gap-2 mb-6">
      <button 
        v-for="shift in ['PAGI', 'SIANG']" 
        :key="shift"
        @click="activeShift = shift"
        class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2"
        :class="activeShift === shift 
          ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-500/20' 
          : 'bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white'"
      >
        <span>{{ shift === 'PAGI' ? '🌅' : '☀️' }}</span>
        Shift {{ shift === 'PAGI' ? 'Pagi' : 'Siang' }}
      </button>
    </div>

    <!-- MAIN WEEKLY GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
      <div 
        v-for="day in daysOfWeek" 
        :key="day"
        class="rounded-2xl border border-white/8 flex flex-col min-h-[400px] overflow-hidden"
        style="background: var(--card)"
      >
        <!-- Day Header -->
        <div class="px-4 py-3 border-b border-white/8 bg-white/2 flex items-center justify-between">
          <span class="font-bold text-sm tracking-wider text-white uppercase">{{ day }}</span>
          <span v-if="!hasLessons(activeShift, day)" class="text-xs text-green-400 font-semibold">Free</span>
        </div>

        <!-- Day Slots -->
        <div class="flex-1 p-3 flex flex-col gap-3 justify-start">
          <!-- FREE DAY STATE -->
          <div 
            v-if="!hasLessons(activeShift, day)" 
            class="flex-1 flex flex-col items-center justify-center py-10 text-center space-y-2"
          >
            <div class="w-10 h-10 rounded-full bg-green-500/10 text-green-400 flex items-center justify-center text-lg">
              ✓
            </div>
            <div class="text-xs font-bold text-green-400">Hari Libur / Free</div>
          </div>

          <!-- SLOTS STATE -->
          <template v-else>
            <div 
              v-for="slot in getSlotsForDay(activeShift, day)" 
              :key="slot.id"
              class="rounded-xl border p-3 flex flex-col gap-1.5 relative overflow-hidden transition-all duration-200"
              :class="getSlotClass(activeShift, day, slot)"
            >
              <!-- Time & Period Header -->
              <div class="flex items-center justify-between">
                <span class="text-[9px] font-bold text-slate-500 uppercase">
                  {{ slot.is_kbm ? `JP ${slot.jam_ke}` : slot.keterangan }}
                </span>
                <span class="text-[9px] font-mono text-slate-400">
                  ⏰ {{ formatTime(slot.waktu_mulai) }} - {{ formatTime(slot.waktu_selesai) }}
                </span>
              </div>

              <!-- Lesson / Status -->
              <div class="mt-1">
                <!-- If BREAK -->
                <div v-if="!slot.is_kbm" class="text-xs font-bold text-amber-500 flex items-center gap-1.5">
                  ☕ {{ slot.keterangan }}
                </div>
                <!-- If KBM -->
                <template v-else>
                  <div v-if="getLesson(activeShift, day, slot.jam_ke)" class="space-y-1">
                    <div class="text-xs font-black text-white leading-tight">
                      {{ getLesson(activeShift, day, slot.jam_ke).class_subject?.subject?.nama_mapel }}
                    </div>
                    <div class="text-[10px] text-slate-400 flex items-center gap-1">
                      <span>🏫 Kelas:</span>
                      <span class="font-bold text-indigo-400">{{ getLesson(activeShift, day, slot.jam_ke).class_subject?.clas?.nama_kelas }}</span>
                    </div>
                  </div>
                  <div v-else class="text-[10px] font-bold text-slate-600 uppercase tracking-wider italic">
                    KOSONG
                  </div>
                </template>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  timetable: Array,
  jpSchedules: Object
});

const activeShift = ref('PAGI');

const daysOfWeek = ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'];

const navigation = [
  {
    label: 'KBM (Kegiatan Belajar Mengajar)',
    items: [
      { href: '/guru/dashboard', icon: '📊', label: 'Dashboard' },
      { href: '/guru/jadwal', icon: '📅', label: 'Jadwal Mengajar' },
      { href: '/guru/riwayat-jurnal', icon: '📜', label: 'Riwayat Jurnal Mengajar' },
      { href: '/guru/materi', icon: '📁', label: 'Materi Pembelajaran' },
    ],
  },
  {
    label: 'Evaluasi & Penilaian',
    items: [
      { href: '/guru/pemetaan-materi', icon: '🗺️', label: 'Pemetaan Materi' },
      { href: '/guru/bank-soal', icon: '📝', label: 'Bank Soal & Ujian Live' },
      { href: '/guru/nilai-sumatif', icon: '📊', label: 'Nilai Sumatif' },
      { href: '/guru/nilai-akhir', icon: '📋', label: 'Nilai Akhir' },
      { href: '/guru/rapor-preview', icon: '📑', label: 'Rapor Preview' },
    ],
  },
];

const hasLessons = (shift, hari) => {
  if (!props.timetable) return false;
  return props.timetable.some(t => 
    t.hari === hari && 
    t.class_subject?.clas?.shift_operasional === shift
  );
};

const getSlotsForDay = (shift, hari) => {
  if (!props.jpSchedules || !props.jpSchedules[shift]) return [];
  return props.jpSchedules[shift][hari] || [];
};

const getLesson = (shift, hari, jamKe) => {
  if (!props.timetable) return null;
  return props.timetable.find(t => 
    t.hari === hari && 
    t.jam_ke === jamKe && 
    t.class_subject?.clas?.shift_operasional === shift
  );
};

const formatTime = (timeStr) => {
  if (!timeStr) return '';
  return timeStr.substring(0, 5);
};

const getSlotClass = (shift, hari, slot) => {
  if (!slot.is_kbm) {
    return 'border-amber-500/20 bg-amber-500/5';
  }
  const lesson = getLesson(shift, hari, slot.jam_ke);
  if (lesson) {
    return 'border-indigo-500/30 bg-indigo-500/5 hover:border-indigo-500/50';
  }
  return 'border-white/5 bg-white/1 opacity-60';
};
</script>

<style scoped>
</style>
