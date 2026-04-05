@extends('coreui::layouts.mini-app')

@section('content')
<div class="container py-3">
  <div class="row justify-content-center mb-3">
    <div class="col-md-12">
      <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('telegram.home') }}" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>
      </div>
    </div>
  </div>
  <div class="row mb-3">
    <div class="col-md-8 col-lg-6">
      <div class="card shadow">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0"><i class="bi bi-gem2 me-2"></i>Harga Emas Dunia</h4>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-3">
              <label>Mata Uang</label>
              <select id="currency-select" class="form-select">
                <option value="">-- Pilih Mata Uang --</option>
              </select>
            </div>
            <div class="col-md-3">
              <label>Rentang Waktu</label>
              <select id="range-select" class="form-select">
                <option value="6">6 jam terakhir</option>
                <option value="24">24 jam terakhir</option>
                <option value="7" selected>7 hari</option>
                <option value="30">30 hari</option>
                <option value="90">90 hari</option>
              </select>
            </div>
            <div class="col-md-3 align-self-end">
              <button id="refresh-btn" class="btn btn-primary">Refresh</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <h4>Tabel Harga Terkini</h4>
      <div id="loading-table" class="text-center d-none">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loding...</span>
        </div>
        <p>
          Memuat data...
        </p>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered" id="price-table">
          <thead>
            <tr><th>Mata Uang</th><th>Ounce</th><th>Gram</th><th>Tola</th><th>Update</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
    <div class="col-md-6">
      <h4>Chart History</h4>
      <div style="position: relative;">
        <canvas id="price-chart" width="400" height="200"></canvas>
        <div id="loading-chart" class="text-center d-none" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
          <div class="spinner-border text-primary"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  let chartInstance = null;
  let isLoading = false;
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

  async function fetchHistory(currency, hours = null, days = 30) {
    let url = `${apiBase}/history?currency=${currency}`;
    if (hours) {
      url += `&hours=${hours}`;
    } else {
      url += `&days=${days}`;
    }
    const res = await fetch(url);
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

    if (!history.length) {
      document.getElementById('price-chart').style.display = 'none';
      return;
    }
    document.getElementById('price-chart').style.display = 'block';

    const labels = history.map(h => {
    const dt = new Date(h.price_date);
    return dt.toLocaleString();
    });
    const prices = history.map(h => parseFloat(h[unit]));
    chartInstance = new Chart(ctx,
      {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: `Harga per ${unit}`,
            data: prices,
            borderColor: 'rgb(255, 193, 7)',
            backgroundColor: 'rgba(255, 193, 7, 0.1)',
            tension: 0.2,
            pointRadius: 2,
            pointHoverRadius: 5
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: {
            tooltip: {
              callbacks: {
                label: function(context) {
                  return `${context.dataset.label}: ${context.raw.toLocaleString()}`;
                }
              }
            },
            zoom: {
              zoom: {
                wheel: {
                  enabled: true
                },
                pinch: {
                  enabled: true
                },
                mode: 'x',
              }
            }
          },
          scales: {
            x: {
              title: {
                display: true, text: 'Waktu'
              },
              ticks: {
                maxRotation: 45,
                autoSkip: true,
                maxTicksLimit: 10
              }
            },
            y: {
              title: {
                display: true, text: `Harga (${unit})`
              },
              beginAtZero: false
            }
          }
        }
      });
  }

  async function loadAll() {
    if (isLoading) return;
    isLoading = true;

    const refreshBtn = document.getElementById('refresh-btn');
    const currencySelect = document.getElementById('currency-select');
    const rangeSelect = document.getElementById('range-select');

    const loadingTable = document.getElementById('loading-table');
    const loadingChart = document.getElementById('loading-chart');
    const priceChart = document.getElementById('price-chart');

    loadingTable.classList.remove('d-none');
    loadingChart.classList.remove('d-none');
    priceChart.style.opacity = "0.5";
    refreshBtn.disabled = true;
    currencySelect.disabled = true;
    rangeSelect.disabled = true;

    try {
      const currency = currencySelect.value;
      const rangeValue = parseInt(rangeSelect.value, 10);
      let hours = null,
      days = 30;
      if (rangeValue <= 24) {
        hours = rangeValue;
      } else {
        days = rangeValue;
      }
      const latestData = await fetchLatest(currency);
      renderTable(latestData);
      let targetCurrency = currency;
      if (!targetCurrency && latestData.length) {
        targetCurrency = latestData[0].currency;
        currencySelect.value = targetCurrency;
      }
      if (targetCurrency) {
        const history = await fetchHistory(targetCurrency, hours, days);
        if (history && history.length) {
          renderChart(history, 'gram');
        } else {
          alert("No history data found for: " + targetCurrency);
          priceChart.style.display = 'none';
        }
      }
    } catch (err) {
      console.error(err);
    } finally {
      loadingTable.classList.add('d-none');
      loadingChart.classList.add('d-none');
      priceChart.style.opacity = "1";
      refreshBtn.disabled = false;
      currencySelect.disabled = false;
      rangeSelect.disabled = false;
      isLoading = false;
    }
  }

  document.getElementById('refresh-btn').addEventListener('click', loadAll);
  document.getElementById('currency-select').addEventListener('change', loadAll);
  document.getElementById('range-select').addEventListener('change', loadAll);

  document.addEventListener('DOMContentLoaded', function() {
  fetchCurrencies().then(() => loadAll()).catch(err => alert(err.message));
  });
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