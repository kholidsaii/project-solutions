<script setup lang="ts">
import { computed } from 'vue';
import VueApexCharts from "vue3-apexcharts";
import type { ApexOptions } from 'apexcharts';

const props = defineProps<{
  seriesData: any[],
  chartType: 'bar' | 'donut',
  height?: number
}>();

// Opsi dinamis berdasarkan tipe chart
const chartOptions = computed<ApexOptions>(() => {
  const baseOptions: ApexOptions = {
    chart: { 
      toolbar: { show: false }, 
      fontFamily: 'Inter, sans-serif',
      sparkline: { enabled: false }
    },
    colors: ['#6366f1', '#22d3ee', '#10b981', '#f59e0b', '#ef4444'],
    dataLabels: { enabled: false },
    stroke: { show: props.chartType === 'bar', width: 2, colors: ['transparent'] },
  };

  if (props.chartType === 'bar') {
    return {
      ...baseOptions,
      plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
      xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        labels: { style: { fontSize: '10px', fontWeight: 600, colors: '#94a3b8' } }
      },
      grid: { borderColor: '#f1f5f9', strokeDashArray: 4 }
    };
  } else {
    return {
      ...baseOptions,
      labels: ['Retail', 'Project', 'Wabku', 'Maintenance', 'Other'],
      legend: { position: 'bottom', fontSize: '12px', fontWeight: 600 },
      plotOptions: { pie: { donut: { size: '70%' } } }
    };
  }
});
</script>

<template>
  <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm transition-all">
    <div class="mb-4">
      <slot name="title"></slot>
    </div>
    <VueApexCharts 
      :type="chartType" 
      :height="height || (chartType === 'bar' ? 320 : 280)" 
      :options="chartOptions" 
      :series="seriesData" 
    />
  </div>
</template>