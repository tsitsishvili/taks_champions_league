<template>
  <div class="container mx-auto px-4 py-8">
    <div class="header-section mb-8 py-10 rounded-lg shadow-lg relative overflow-hidden">
      <div class="absolute top-0 left-0 w-full h-full">
        <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-10 rounded-full transform translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 left-0 w-32 h-32 bg-white opacity-10 rounded-full transform -translate-x-1/2 translate-y-1/2"></div>
      </div>
      <div class="relative z-10">
        <h1 class="text-4xl font-bold text-center text-white mb-4 tracking-wide">Champions League Simulation</h1>
      </div>
    </div>

    <div class="flex flex-wrap justify-center gap-4 mb-8">
      <button
        @click="simulate"
        class="action-button bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg shadow-md flex items-center"
        :disabled="loading"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
        </svg>
        {{ loading ? 'Simulating...' : 'Simulate All Matches' }}
      </button>

      <button
        @click="simulateNextWeek"
        class="action-button bg-purple-500 hover:bg-purple-600 text-white font-bold py-3 px-6 rounded-lg shadow-md flex items-center"
        :disabled="loading"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />
        </svg>
        {{ loading ? 'Simulating...' : 'Simulate Next Week' }}
      </button>

      <button
        @click="reset"
        class="action-button bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-6 rounded-lg shadow-md flex items-center"
        :disabled="loading"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
        </svg>
        Reset League
      </button>

      <button
        @click="initialize"
        class="action-button bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg shadow-md flex items-center"
        :disabled="loading"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
        </svg>
        Initialize League
      </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- League Table -->
      <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-semibold mb-4">League Standings</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
            <thead>
              <tr class="bg-gradient-to-r from-blue-500 to-blue-700 text-white">
                <th class="py-3 px-4 border-b text-center">Pos</th>
                <th class="py-3 px-4 border-b text-left">Team</th>
                <th class="py-3 px-4 border-b text-center">P</th>
                <th class="py-3 px-4 border-b text-center">W</th>
                <th class="py-3 px-4 border-b text-center">D</th>
                <th class="py-3 px-4 border-b text-center">L</th>
                <th class="py-3 px-4 border-b text-center">GF</th>
                <th class="py-3 px-4 border-b text-center">GA</th>
                <th class="py-3 px-4 border-b text-center">GD</th>
                <th class="py-3 px-4 border-b text-center">Pts</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, index) in table"
                :key="row.team.id"
                :class="[
                  index % 2 === 0 ? 'bg-gray-50' : '',
                  index === 0 ? 'bg-green-50 border-l-4 border-green-500' : '',
                  'hover:bg-blue-50 transition-colors duration-150'
                ]"
              >
                <td class="py-3 px-4 border-b text-center font-bold">{{ row.position }}</td>
                <td class="py-3 px-4 border-b font-medium">{{ row.team.name }}</td>
                <td class="py-3 px-4 border-b text-center">{{ row.played }}</td>
                <td class="py-3 px-4 border-b text-center">{{ row.wins }}</td>
                <td class="py-3 px-4 border-b text-center">{{ row.draws }}</td>
                <td class="py-3 px-4 border-b text-center">{{ row.losses }}</td>
                <td class="py-3 px-4 border-b text-center">{{ row.goals_for }}</td>
                <td class="py-3 px-4 border-b text-center">{{ row.goals_against }}</td>
                <td class="py-3 px-4 border-b text-center">{{ row.goal_difference }}</td>
                <td class="py-3 px-4 border-b text-center font-bold">{{ row.points }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Championship Predictions -->
      <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-semibold mb-4">Prediction of Championship</h2>
        <div class="grid grid-cols-1 gap-4">
          <div
            v-for="prediction in predictions"
            :key="prediction.team.id"
            class="prediction-card p-4 rounded-lg shadow-md"
          >
            <div class="flex justify-between items-center">
              <span class="font-bold text-lg">{{ prediction.team.name }} - {{ prediction.probability }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Match Results -->
      <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-semibold mb-4">Match Results</h2>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <div v-for="match in matches" :key="match.id" class="match-card mb-4 pb-4 border-b border-gray-200 last:border-0">
            <div class="flex items-center flex-wrap">
              <div class="w-full md:w-auto mb-2 md:mb-0">
                <span class="font-medium">Week {{ match.week }}:</span>
              </div>
              <div class="flex items-center flex-1 justify-center">
                <span class="mr-2 team-name">{{ match.home_team.name }}</span>
                <span class="score">{{ match.played ? match.home_score : '-' }}</span>
                <span class="mx-2">:</span>
                <span class="score">{{ match.played ? match.away_score : '-' }}</span>
                <span class="ml-2 team-name">{{ match.away_team.name }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, watch } from 'vue';

export default {
  setup() {
    const matches = ref([]);
    const table = ref([]);
    const predictions = ref([]);
    const loading = ref(false);

    const load = async () => {
      loading.value = true;
      try {
        const response = await fetch('/api/league');
        const data = await response.json();
        matches.value = data.matches;
        table.value = data.table;
        predictions.value = data.predictions;
      } catch (error) {
        console.error('Error loading league data:', error);
      } finally {
        loading.value = false;
      }
    };

    const simulate = async () => {
      loading.value = true;
      try {
        await fetch('/api/league/simulate', { method: 'POST' });
        await load();
      } catch (error) {
        console.error('Error simulating matches:', error);
        loading.value = false;
      }
    };

    const simulateNextWeek = async () => {
      loading.value = true;
      try {
        const response = await fetch('/api/league/simulate-next-week', { method: 'POST' });
        const data = await response.json();

        if (data.success) {
          console.log(`Simulated week ${data.week}`);
        } else {
          console.log(data.message);
        }

        await load();
      } catch (error) {
        console.error('Error simulating next week:', error);
        loading.value = false;
      }
    };

    const reset = async () => {
      loading.value = true;
      try {
        await fetch('/api/league/reset', { method: 'POST' });
        await load();
      } catch (error) {
        console.error('Error resetting league:', error);
        loading.value = false;
      }
    };

    const initialize = async () => {
      loading.value = true;
      try {
        await fetch('/api/league/initialize', { method: 'POST' });
        await load();
      } catch (error) {
        console.error('Error initializing league:', error);
        loading.value = false;
      }
    };


    onMounted(async () => {
      // First, try to load existing data
      await load();

      // Only initialize if no matches exist
      if (matches.value.length === 0) {
        await initialize();
      }
    });

    return {
      matches,
      table,
      predictions,
      simulate,
      simulateNextWeek,
      reset,
      initialize,
      loading
    };
  }
}
</script>

<style scoped>
@import './ChampionsLeagueComponent.css';
</style>
