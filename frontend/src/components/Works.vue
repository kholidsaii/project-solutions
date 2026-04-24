<script setup lang="ts">
import { computed } from 'vue';
import VueApexCharts from "vue3-apexcharts";
import type { ApexOptions } from 'apexcharts';

// Kita tambahkan tipe custom agar TypeScript tidak komplain
const props = defineProps<{
  seriesData: any[],
  chartType: 'bar' | 'donut' | 'bar-priority' | 'donut-mini',
  height?: number,
  title?: string
}>();

const chartOptions = computed<ApexOptions>(() => {
  const baseOptions: ApexOptions = {
    chart: { 
      toolbar: { show: false }, 
      fontFamily: 'Inter, sans-serif',
      // Tambahkan ini supaya chart tidak goyang saat render
      animations: { enabled: true, speed: 800 }
    },
    colors: ['#6366f1', '#22d3ee', '#10b981', '#f59e0b', '#ef4444'],
    dataLabels: { enabled: false },
    legend: { show: false } // Kita pakai legend manual di HTML
  };

  // --- 1. LOGIKA BAR PRIORITY (WARNA WARNI) ---
  if (props.chartType === 'bar-priority') {
    return {
      ...baseOptions,
      plotOptions: {
        bar: {
          distributed: true, 
          columnWidth: '45%',
          borderRadius: 4,
        }
      },
      colors: ['#EF4444', '#FBBF24', '#10B981', '#0000FF', '#22D3EE'],
      xaxis: {
        // Label bertumpuk (Array di dalam Array)
        categories: [['Urgent', '800'], ['High', '50'], ['Medium', '130'], ['Low', '150'], ['Planned', '210']],
        labels: {
          style: {
            fontSize: '11px',
            fontWeight: 800,
            colors: ['#EF4444', '#FBBF24', '#10B981', '#0000FF', '#22D3EE']
          }
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      yaxis: { show: true, labels: { style: { colors: '#94a3b8' } } },
      grid: { borderColor: '#f1f5f9', strokeDashArray: 4 }
    };
  }

  // --- 2. LOGIKA DONUT MINI (DENGAN TOTAL DI TENGAH) ---
  if (props.chartType === 'donut-mini' || props.chartType === 'donut') {
    return {
      ...baseOptions,
      labels: ['Retail', 'Project', 'Wabku', 'SPJ', 'Maintenance'],
      stroke: { width: 0 },
      plotOptions: {
        pie: {
          donut: {
            size: '72%',
            labels: {
              show: true,
              total: {
                show: true,
                label: '100%',
                formatter: () => '1.420',
                fontSize: '14px',
                fontWeight: 900,
                color: '#2E3A8C'
              }
            }
          }
        }
      }
    };
  }

  // --- 3. LOGIKA BAR STANDAR (BULANAN) ---
  return {
    ...baseOptions,
    plotOptions: { 
      bar: { 
        columnWidth: '40%', 
        borderRadius: 3,
        colors: { backgroundBarColors: ['#f8fafc'], backgroundBarOpacity: 1 } 
      } 
    },
    xaxis: {
      categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
      labels: { style: { fontSize: '10px', fontWeight: 600, colors: '#94a3b8' } },
      axisBorder: { show: false }
    },
    grid: { show: true, borderColor: '#f1f5f9', yaxis: { lines: { show: true } } }
  };
});

// Helper untuk menentukan tipe chart asli bagi ApexCharts
const getRealChartType = computed(() => {
  if (props.chartType === 'bar-priority') return 'bar';
  if (props.chartType === 'donut-mini') return 'donut';
  return props.chartType;
});
</script>

<template>
  <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm h-full flex flex-col justify-between">
    <div v-if="title" class="flex items-center gap-2 mb-4">
      <i class="fas fa-th-large text-[#2E3A8C] text-[10px]"></i>
      <h3 class="text-[12px] font-black text-[#2E3A8C] uppercase tracking-wider">{{ title }}</h3>
    </div>

    <div class="flex-1 flex items-center justify-center">
      <VueApexCharts 
        :type="getRealChartType" 
        :height="height || 300" 
        :options="chartOptions" 
        :series="seriesData" 
        class="w-full"
      />
    </div>
  </div>
</template>