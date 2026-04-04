@extends('coreui::layouts.mini-app')

@section('content')
<div class="container mt-4">
  <h2>Harga Emas Dunia</h2>
  <div class="row mb-3">
    <div class="col-md-3">
      <label>Mata Uang</label>
      <select id="currency-select" class="form-select">
        <option value="">-- Pilih Mata Uang --</option>
      </select>
    </div>
    <div class="col-md-3">
      <label>Rentang Hari (Chart)</label>
      <select id="days-select" class="form-select">
        <option value="7">7 hari</option>
        <option value="30" selected>30 hari</option>
        <option value="90">90 hari</option>
      </select>
    </div>
    <div class="col-md-3 align-self-end">
      <button id="refresh-btn" class="btn btn-primary">Refresh</button>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <h4>Tabel Harga Terkini</h4>
      <div id="loading-table" class="text-center d-none">
        Loading...
      </div>
      <table class="table table-bordered" id="price-table">
        <thead>
          <tr><th>Mata Uang</th><th>Ounce</th><th>Gram</th><th>Tola</th><th>Update</th></tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    <div class="col-md-6">
      <h4>Chart History</h4>
      <canvas id="price-chart" width="400" height="200"></canvas>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  let chartInstance = null;
  const apiBase = '{{ secure_url(config("app.url")) }}/api/gold-price';

  async function fetchCurrencies() {
    const res = await fetch(`${apiBase}/currencies`);
    const data = await res.json();
    const select = document.getElementById('currency-select');
    select.innerHTML = '<option value="">-- Semua Mata Uang --</option>';
    data.forEach(curr => {
    const opt = document.createElement('option');
    opt.value = curr;
    opt.textContent = curr;
    select.appendChild(opt);
    });
  }

  async function fetchLatest(currency = '') {
    const url = currency ? `${apiBase}/latest?currency=${currency}`: `${apiBase}/latest`;
    const res = await fetch(url);
    return await res.json();
  }

  async function fetchHistory(currency, days) {
    const res = await fetch(`${apiBase}/history?currency=${currency}&days=${days}`);
    return await res.json();
  }

  function renderTable(data) {
    const tbody = document.querySelector('#price-table tbody');
    tbody.innerHTML = '';
    data.forEach(item => {
    const row = `<tr>
    <td>${item.currency}</td>
    <td>${Number(item.ounce).toLocaleString()}</td>
    <td>${Number(item.gram).toLocaleString()}</td>
    <td>${Number(item.tola).toLocaleString()}</td>
    <td>${new Date(item.price_date).toLocaleString()}</td>
    </tr>`;
    tbody.insertAdjacentHTML('beforeend', row);
    });
  }

  function renderChart(history, unit = 'gram') {
    const ctx = document.getElementById('price-chart').getContext('2d');
    if (chartInstance) chartInstance.destroy();
    const labels = history.map(h => new Date(h.price_date).toLocaleDateString());
    const prices = history.map(h => h[unit]);
    chartInstance = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: `Harga per ${unit}`,
          data: prices,
          borderColor: 'rgb(75, 192, 192)',
          tension: 0.1
        }]
      },
      options: {
        responsive: true
      }
    });
  }

  async function loadAll() {
    const loading = document.getElementById('loading-table');
    loading.classList.remove('d-none');
    try {
      const currency = document.getElementById('currency-select').value;
      const days = document.getElementById('days-select').value;
      const latestData = await fetchLatest(currency);
      renderTable(latestData);
      if (currency) {
        const history = await fetchHistory(currency, days);
        renderChart(history, 'gram');
      } else if (latestData.length > 0) {
        // default currency pertama untuk chart
        const firstCurrency = latestData[0].currency;
        const history = await fetchHistory(firstCurrency, days);
        renderChart(history, 'gram');
        document.getElementById('currency-select').value = firstCurrency;
      }
    } catch (err) {
      console.error(err);
    } finally {
      loading.classList.add('d-none');
    }
  }

  document.getElementById('refresh-btn').addEventListener('click', loadAll);
  document.getElementById('currency-select').addEventListener('change', loadAll);
  document.getElementById('days-select').addEventListener('change', () => {
  const curr = document.getElementById('currency-select').value;
  if (curr) loadAll();
  });

  fetchCurrencies().then(() => loadAll());
</script>
@endsection

@push('styles')
<style>
  /* Tema Telegram */
  body {
    background-color: var(--tg-theme-bg-color);
    color: var(--tg-theme-text-color);
  }
  .card {
    background-color: var(--tg-theme-secondary-bg-color);
    border: none;
  }
  .card-header {
    background-color: var(--tg-theme-button-color);
    color: var(--tg-theme-button-text-color);
    border-bottom: none;
  }
  .btn-primary {
    background-color: var(--tg-theme-button-color);
    border-color: var(--tg-theme-button-color);
    color: var(--tg-theme-button-text-color);
  }
  .btn-outline-primary {
    color: var(--tg-theme-button-color);
    border-color: var(--tg-theme-button-color);
  }
  .btn-outline-primary:hover {
    background-color: var(--tg-theme-button-color);
    color: var(--tg-theme-button-text-color);
  }
  .btn-outline-secondary {
    color: var(--tg-theme-hint-color);
    border-color: var(--tg-theme-hint-color);
  }
  .btn-outline-secondary:hover {
    background-color: var(--tg-theme-hint-color);
    color: var(--tg-theme-button-text-color);
  }
  .text-muted {
    color: var(--tg-theme-text-color) !important;
  }
  .table {
    color: var(--tg-theme-text-color);
  }
  .table-hover tbody tr:hover {
    background-color: text-white;
  }
  .table td, .table th {
    border-color: var(--tg-theme-section-separator-color);
  }
</style>
@endpush