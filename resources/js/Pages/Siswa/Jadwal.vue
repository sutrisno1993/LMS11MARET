<template>
  <Head title="Jadwal Kelas Mingguan" />

  <AppLayout
    title="Jadwal Kelas Mingguan"
    :subtitle="`Jadwal Pelajaran Kelas ${kelas} — Shift ${shift === 'PAGI' ? 'Pagi' : 'Siang'}`"
    :navigation="navigation"
  >
    <template #topbar-actions>
      <div class="flex items-center gap-1.5 bg-indigo-500/10 border border-indigo-500/20 px-3 py-1.5 rounded-full text-xs font-semibold text-indigo-400">
        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
        🏫 Kelas {{ kelas }}
      </div>
    </template>

    <!-- MAIN WEEKLY GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 max-w-7xl mx-auto">
      <div 
        v-for="day in daysOfWeek" 
        :key="day"
        class="rounded-2xl border border-white/8 flex flex-col min-h-[400px] overflow-hidden"
        style="background: var(--card)"
      >
        <!-- Day Header -->
        <div class="px-4 py-3 border-b border-white/8 bg-white/2 flex items-center justify-between">
          <span class="font-bold text-sm tracking-wider text-white uppercase">{{ day }}</span>
          <span v-if="!hasLessons(day)" class="text-xs text-green-400 font-semibold">Free</span>
        </div>

        <!-- Day Slots -->
        <div class="flex-1 p-3 flex flex-col gap-3 justify-start">
          <!-- FREE DAY STATE -->
          <div 
            v-if="!hasLessons(day)" 
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
              v-for="slot in getSlotsForDay(day)" 
              :key="slot.id"
              class="rounded-xl border p-3 flex flex-col gap-1.5 relative overflow-hidden transition-all duration-200"
              :class="getSlotClass(day, slot)"
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
                  <div v-if="getLesson(day, slot.jam_ke)" class="space-y-1">
                    <div class="text-xs font-black text-white leading-tight">
                      {{ getLesson(day, slot.jam_ke).class_subject?.subject?.nama_mapel }}
                    </div>
                    <div class="text-[10px] text-slate-400 flex items-center gap-1">
                      <span>👨‍🏫 Guru:</span>
                      <span class="font-bold text-indigo-400">{{ getLesson(day, slot.jam_ke).teacher?.nama_guru }}</span>
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
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  timetable: Array,
  jpSchedules: Object,
  kelas: String,
  shift: String,
});

const daysOfWeek = ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'];

const navigation = [
  {
    label: 'Akademik',
    items: [
      { href: '/siswa/dashboard', icon: '🏠', label: 'Beranda' },
      { href: '/siswa/scan-qr', icon: '📷', label: 'Scan QR Presensi', badge: 'Live' },
      { href: '/siswa/materi', icon: '📁', label: 'Materi Belajar' },
      { href: '/siswa/jadwal', icon: '📅', label: 'Jadwal Kelas' },
    ],
  },
  {
    label: 'Laporan',
    items: [
      { href: '/siswa/nilai', icon: '📊', label: 'Nilai & Capaian' },
      { href: '/siswa/kuesioner', icon: '📝', label: 'Evaluasi Pembelajaran' },
    ],
  },
];

const hasLessons = (hari) => {
  if (!props.timetable) return false;
  return props.timetable.some(t => t.hari === hari);
};

const getSlotsForDay = (hari) => {
  if (!props.jpSchedules) return [];
  return props.jpSchedules[hari] || [];
};

const getLesson = (hari, jamKe) => {
  if (!props.timetable) return null;
  return props.timetable.find(t => t.hari === hari && t.jam_ke === jamKe);
};

const formatTime = (timeStr) => {
  if (!timeStr) return '';
  return timeStr.substring(0, 5);
};

const getSlotClass = (hari, slot) => {
  if (!slot.is_kbm) {
    return 'border-amber-500/20 bg-amber-500/5';
  }
  const lesson = getLesson(hari, slot.jam_ke);
  if (lesson) {
    return 'border-indigo-500/30 bg-indigo-500/5 hover:border-indigo-500/50';
  }
  return 'border-white/5 bg-white/1 opacity-60';
};
</script>
