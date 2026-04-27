<script setup lang="ts">
import { computed } from 'vue';
import VueApexCharts from "vue3-apexcharts";
import type { ApexOptions } from 'apexcharts';

const props = defineProps<{
  seriesData: any[],
  chartType: 'bar' | 'donut' | 'bar-priority' | 'donut-mini',
  height?: number,
  title?: string,
  labels?: string[];
}>();

const chartOptions = computed<ApexOptions>(() => {
  const baseOptions: ApexOptions = {
    chart: { 
      toolbar: { show: false }, 
      fontFamily: 'Plus Jakarta Sans, sans-serif',
      // Hapus 'easing' di sini untuk memperbaiki error TS2353
      animations: { 
        enabled: true, 
        speed: 1000 
      },
      background: 'transparent'
    },
    stroke: { show: true, width: 2, colors: ['transparent'] },
    dataLabels: { enabled: false },
    legend: { show: false },
    tooltip: {
      theme: 'light',
      y: { formatter: (val) => `${val} Items` }
    }
  };

  if (props.chartType === 'bar-priority') {
    return {
      ...baseOptions,
      plotOptions: {
        bar: {
          distributed: true, 
          columnWidth: '55%',
          borderRadius: 8,
          dataLabels: { position: 'top' }
        }
      },
      colors: ['#EF4444', '#FBBF24', '#10B981', '#6366F1', '#22D3EE'],
      xaxis: {
        categories: [['Urgent'], ['High'], ['Medium'], ['Low'], ['Planned']],
        labels: {
          style: {
            fontSize: '10px',
            fontWeight: 800,
            colors: ['#EF4444', '#FBBF24', '#10B981', '#6366F1', '#22D3EE']
          }
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      grid: { borderColor: '#f1f5f9', strokeDashArray: 4 }
    };
  }

  if (props.chartType === 'donut' || props.chartType === 'donut-mini') {
    return {
      ...baseOptions,
      colors: ['#6366F1', '#22D3EE', '#10B981', '#F59E0B', '#EF4444'],
      // Ambil label dari props agar sinkron dengan data filter di sidebar
      labels: props.labels || ['Retail', 'Project', 'Wabku', 'SPJ', 'Maintenance'],
      plotOptions: {
        pie: {
          donut: {
            size: '75%',
            labels: {
              show: true,
              total: {
                show: true,
                label: 'TOTAL',
                fontSize: '10px',
                fontWeight: 800,
                color: '#94a3b8',
                formatter: (w: any) => {
                  const total = w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0);
                  return total.toString(); // Harus string Lid!
                }
              },
              value: {
                fontSize: '22px',
                fontWeight: 900,
                color: '#1e293b',
                offsetY: 5,
                // PAKSA JADI STRING (Fix Error 2769)
                formatter: (val: string | number) => {
                  return typeof val === 'number' ? val.toString() : val;
                }
              }
            }
          }
        }
      }
    };
  }

  return baseOptions;
});

const getRealChartType = computed(() => {
  if (props.chartType === 'bar-priority') return 'bar';
  if (props.chartType === 'donut-mini') return 'donut';
  return props.chartType;
});
</script>

<template>
  <div class="group relative flex h-full flex-col overflow-hidden rounded-4xl border border-slate-100 bg-white p-6 shadow-[0_10px_40px_-15px_rgba(0,0,0,0.05)] transition-all duration-500 hover:shadow-[0_20px_50px_-20px_rgba(99,102,241,0.15)]">
    
    <div class="absolute top-0 left-0 h-1 w-full bg-linear-to-r from-transparent via-indigo-500/20 to-transparent opacity-0 transition-opacity group-hover:opacity-100"></div>

    <div v-if="title" class="mb-6 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <div class="h-6 w-2 rounded-full bg-indigo-500"></div>
        <h3 class="text-[11px] font-black uppercase tracking-[0.15em] text-slate-700">
          {{ title }}
        </h3>
      </div>
      <div class="flex gap-1">
        <div class="h-1.5 w-1.5 rounded-full bg-slate-200"></div>
        <div class="h-1.5 w-1.5 rounded-full bg-slate-200"></div>
      </div>
    </div>

    <div class="relative flex flex-1 items-center justify-center min-h-55">
      <VueApexCharts 
        :type="getRealChartType" 
        :height="height || 280" 
        :options="chartOptions" 
        :series="seriesData" 
        class="w-full transition-transform duration-500 group-hover:scale-[1.02]"
      />
    </div>
  </div>
</template>