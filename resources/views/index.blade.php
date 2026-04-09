@extends('telegram::layouts.mini-app')

@section('title', 'Harga Emas')

@section('content')
<div class="container py-4">
  {{-- Filter Panel --}}
  <div class="row mb-4">
    <div class="col-12">
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header border-0 py-3 px-4">
          <h5 class="mb-0 fw-semibold"><i class="bi bi-funnel-fill me-2 text-primary"></i>Filter & Pencarian</h5>
        </div>
        <div class="card-body px-4 pb-4">
          <div class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="form-label fw-semibold">Cari Mata Uang</label>
              <div class="input-group shadow-sm">
                <span class="input-group-text border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" id="currency-search" class="form-control border-start-0 ps-0" placeholder="Nama / kode ...">
                <button class="btn btn-outline-secondary" type="button" id="clear-search" title="Hapus pencarian">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Pilih Mata Uang</label>
              <select id="currency-select" class="form-select shadow-sm">
                <option value="">-- Semua Mata Uang --</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Rentang Waktu</label>
              <select id="range-select" class="form-select shadow-sm">
                <option value="6">6 jam terakhir</option>
                <option value="24">24 jam terakhir</option>
                <option value="7" selected>7 hari</option>
                <option value="30">30 hari</option>
                <option value="90">90 hari</option>
              </select>
            </div>
            <div class="col-md-3">
              <button id="refresh-btn" class="btn btn-primary w-100 shadow-sm rounded-pill py-2">
                <i class="bi bi-arrow-repeat me-2"></i>Refresh Data
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Konten Utama: Tabel & Chart --}}
  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-header border-0 pt-4 px-4 pb-0">
          <h5 class="mb-0 fw-semibold"><i class="bi bi-table me-2 text-success"></i>Harga Terkini</h5>
          <hr class="my-2">
        </div>
        <div class="card-body p-4 pt-2">
          <div id="loading-table" class="text-center py-5 d-none">
            <div class="spinner-border text-primary mb-2" role="status"></div>
            <p class="text-muted">
              Memuat data harga ...
            </p>
          </div>
          <div class="table-responsive">
            <table class="table align-middle" id="price-table">
              <thead>
                <tr><th>Mata Uang</th><th class="text-end">Ounce (oz)</th><th class="text-end">Gram (g)</th><th class="text-end">Tola</th><th class="text-end">Perubahan (Gram)</th><th>Update</th></tr>
              </thead>
              <tbody id="price-table-body">
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-header border-0 pt-4 px-4 pb-0">
          <h5 class="mb-0 fw-semibold"><i class="bi bi-graph-up me-2 text-info"></i>Grafik Pergerakan</h5>
          <hr class="my-2">
        </div>
        <div class="card-body p-4 pt-2 position-relative" style="min-height: 300px;">
          <div id="loading-chart" class="text-center py-5 d-none">
            <div class="spinner-border text-info mb-2" role="status"></div>
            <p class="text-muted">
              Memuat grafik ...
            </p>
          </div>
          <canvas id="price-chart" width="400" height="250" style="display: none;"></canvas>
          <div id="chart-empty-message" class="text-center py-5 text-muted d-none">
            <i class="bi bi-bar-chart-steps fs-1"></i><p class="mt-2">
              Tidak ada data history untuk mata uang ini.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
  (function() {
  // Gunakan fungsi global dari layout
  const { fetchWithAuth, showToast, escapeHtml } = window.TelegramApp;

  // ======================== MAPPING MATA UANG ========================
  const fiatCurrencies = {
  'USD': 'US Dollar', 'EUR': 'Euro', 'GBP': 'Pound Sterling', 'JPY': 'Yen', 'CNY': 'Yuan Renminbi',
  'IDR': 'Rupiah', 'MYR': 'Malaysian Ringgit', 'SGD': 'Singapore Dollar', 'THB': 'Baht', 'INR': 'Indian Rupee',
  'SAR': 'Saudi Riyal', 'AED': 'UAE Dirham', 'KRW': 'Won', 'RUB': 'Russian Ruble', 'CAD': 'Canadian Dollar',
  'AUD': 'Australian Dollar', 'CHF': 'Swiss Franc', 'NZD': 'New Zealand Dollar', 'ZAR': 'Rand', 'TRY': 'Turkish Lira',
  'BRL': 'Brazilian Real', 'MXN': 'Mexican Peso', 'HKD': 'Hong Kong Dollar', 'TWD': 'New Taiwan Dollar'
  };
  const cryptoCurrencies = {
  'BTC': 'Bitcoin', 'ETH': 'Ethereum', 'USDT': 'Tether', 'BNB': 'Binance Coin', 'XRP': 'Ripple',
  'ADA': 'Cardano', 'SOL': 'Solana', 'DOGE': 'Dogecoin', 'DOT': 'Polkadot', 'MATIC': 'Polygon'
  };
  const currencyNames = { ...fiatCurrencies, ...cryptoCurrencies };
  function getCurrencyName(code) { return currencyNames[code] || code; }

  let chartInstance = null, isLoading = false, allCurrencies = [];
  const apiBase = '{{ config("app.url") }}/api/gold-price';

  function renderCurrencyOptions(filterText) {
  const select = document.getElementById('currency-select');
  const filter = filterText.toLowerCase().trim();
  const currentValue = select.value;
  const filtered = filter === '' ? allCurrencies : allCurrencies.filter(code => {
  const display = (currencyNames[code] || code).toLowerCase();
  return display.includes(filter) || code.toLowerCase().includes(filter);
  });
  select.innerHTML = '<option value="">-- Pilih Mata Uang --</option>';
  filtered.forEach(code => {
  const opt = document.createElement('option');
  opt.value = code;
  opt.textContent = `${getCurrencyName(code)} (${code})`;
  select.appendChild(opt);
  });
  if (currentValue && filtered.includes(currentValue)) select.value = currentValue;
  else if (filtered.length === 1 && filtered[0]) select.value = filtered[0];
  }

  function initCurrencySearch() {
  const searchInput = document.getElementById('currency-search');
  const clearBtn = document.getElementById('clear-search');
  searchInput.addEventListener('input', () => renderCurrencyOptions(searchInput.value));
  clearBtn.addEventListener('click', () => {
  searchInput.value = '';
  renderCurrencyOptions('');
  searchInput.focus();
  });
  }

  async function fetchChangePercent(currency, currentGram) {
  try {
  const data = await fetchWithAuth(`${apiBase}/history?currency=${currency}&days=7`);
  if (data.length < 2) return null;
  const lastTwo = data.slice(-2);
  const prevGram = parseFloat(lastTwo[0].gram);
  const currGram = parseFloat(currentGram);
  if (isNaN(prevGram) || isNaN(currGram) || prevGram === 0) return null;
  return ((currGram - prevGram) / prevGram) * 100;
  } catch (err) {
  console.error(err);
  return null;
  }
  }

  async function fetchCurrencies() {
  const data = await fetchWithAuth(`${apiBase}/currencies`);
  allCurrencies = data;
  renderCurrencyOptions('');
  initCurrencySearch();
  }

  async function fetchLatest(currency = '') {
  const url = currency ? `${apiBase}/latest?currency=${currency}` : `${apiBase}/latest`;
  return await fetchWithAuth(url);
  }

  async function fetchHistory(currency, hours = null, days = 30) {
  let url = `${apiBase}/history?currency=${currency}`;
  if (hours) url += `&hours=${hours}`;
  else url += `&days=${days}`;
  return await fetchWithAuth(url);
  }

  function renderTableWithLoading(data) {
  const tbody = document.getElementById('price-table-body');
  if (!data.length) {
  tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-database-slash"></i> Tidak ada data</td></tr>';
  return;
  }
  tbody.innerHTML = data.map(item => `
  <tr>
  <td class="fw-semibold">${escapeHtml(getCurrencyName(item.currency))} <span class="text-muted small">(${escapeHtml(item.currency)})</span></td>
  <td class="text-end">${Number(item.ounce).toLocaleString()}</td>
  <td class="text-end">${Number(item.gram).toLocaleString()}</td>
  <td class="text-end">${Number(item.tola).toLocaleString()}</td>
  <td class="text-end"><div class="spinner-border spinner-border-sm text-secondary" role="status"><span class="visually-hidden">Loading...</span></div></td>
  <td class="text-nowrap small">${new Date(item.price_date).toLocaleString()}</td>
  </tr>
  `).join('');
  }

  function renderTable(data) {
  const tbody = document.getElementById('price-table-body');
  if (!data.length) {
  tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-database-slash"></i> Tidak ada data</td></tr>';
  return;
  }
  tbody.innerHTML = data.map(item => {
  let changeHtml = '<span class="text-muted">-</span>';
  if (item.change_percent !== null && !isNaN(item.change_percent)) {
  const percent = item.change_percent;
  const isPositive = percent > 0;
  const isZero = percent === 0;
  const colorClass = isPositive ? 'text-success' : (isZero ? 'text-muted' : 'text-danger');
  const sign = isPositive ? '+' : '';
  changeHtml = `<span class="${colorClass} fw-semibold">${sign}${percent.toFixed(2)}%</span>`;
  }
  return `
  <tr>
  <td class="fw-semibold">${escapeHtml(getCurrencyName(item.currency))} <span class="text-muted small">(${escapeHtml(item.currency)})</span></td>
  <td class="text-end">${Number(item.ounce).toLocaleString()}</td>
  <td class="text-end">${Number(item.gram).toLocaleString()}</td>
  <td class="text-end">${Number(item.tola).toLocaleString()}</td>
  <td class="text-end">${changeHtml}</td>
  <td class="text-nowrap small">${new Date(item.price_date).toLocaleString()}</td>
  </tr>
  `;
  }).join('');
  }

  function getTelegramColors() {
  const bodyStyle = getComputedStyle(document.body);
  return {
  textColor: bodyStyle.getPropertyValue('--tg-theme-text-color').trim() || '#212529',
  hintColor: bodyStyle.getPropertyValue('--tg-theme-hint-color').trim() || '#6c757d',
  separatorColor: bodyStyle.getPropertyValue('--tg-theme-section-separator-color').trim() || '#dee2e6',
  bgColor: bodyStyle.getPropertyValue('--tg-theme-bg-color').trim() || '#ffffff'
  };
  }

  function renderChart(history, unit = 'gram') {
  const canvas = document.getElementById('price-chart');
  const emptyMsg = document.getElementById('chart-empty-message');
  if (chartInstance) chartInstance.destroy();
  if (!history.length) {
  canvas.style.display = 'none';
  emptyMsg.classList.remove('d-none');
  return;
  }
  canvas.style.display = 'block';
  emptyMsg.classList.add('d-none');
  const colors = getTelegramColors();
  const labels = history.map(h => new Date(h.price_date).toLocaleString());
  const prices = history.map(h => parseFloat(h[unit]));
  chartInstance = new Chart(canvas, {
  type: 'line',
  data: { labels, datasets: [{ label: `Harga per ${unit}`, data: prices, borderColor: '#f1c40f', backgroundColor: 'rgba(241,196,15,0.05)', borderWidth: 2, pointRadius: 2, pointHoverRadius: 6, pointBackgroundColor: '#e67e22', tension: 0.2, fill: true }] },
  options: {
  responsive: true, maintainAspectRatio: true,
  plugins: { tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${ctx.raw.toLocaleString()}` }, bodyColor: colors.textColor, titleColor: colors.textColor, backgroundColor: colors.bgColor, borderColor: colors.separatorColor, borderWidth: 1 }, legend: { labels: { color: colors.textColor, boxWidth: 12 } } },
  scales: { x: { title: { display: true, text: 'Waktu', color: colors.hintColor, font: { size: 11 } }, ticks: { color: colors.textColor, maxRotation: 45, autoSkip: true, maxTicksLimit: 8 }, grid: { color: colors.separatorColor + '40' } }, y: { title: { display: true, text: `Harga (${unit})`, color: colors.hintColor, font: { size: 11 } }, beginAtZero: false, ticks: { color: colors.textColor, callback: (value) => { if (value >= 1e6) return (value / 1e6).toFixed(1) + 'M'; if (value >= 1e3) return (value / 1e3).toFixed(1) + 'K'; return value.toLocaleString(); }, maxTicksLimit: 6, autoSkip: true }, grid: { color: colors.separatorColor + '40' } } }
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
  const chartEmpty = document.getElementById('chart-empty-message');

  loadingTable.classList.remove('d-none');
  loadingChart.classList.remove('d-none');
  priceChart.style.display = 'none';
  chartEmpty.classList.add('d-none');
  refreshBtn.disabled = true;
  currencySelect.disabled = true;
  rangeSelect.disabled = true;

  try {
  const currency = currencySelect.value;
  const rangeValue = parseInt(rangeSelect.value, 10);
  let hours = null, days = 30;
  if (rangeValue <= 24) hours = rangeValue;
  else days = rangeValue;

  const latestData = await fetchLatest(currency);
  renderTableWithLoading(latestData);
  const changePromises = latestData.map(async (item) => {
  const percent = await fetchChangePercent(item.currency, item.gram);
  return { currency: item.currency, percent };
  });
  const changes = await Promise.all(changePromises);
  const enrichedData = latestData.map(item => {
  const change = changes.find(c => c.currency === item.currency);
  return { ...item, change_percent: change ? change.percent : null };
  });
  renderTable(enrichedData);

  let targetCurrency = currency;
  if (!targetCurrency && latestData.length) {
  targetCurrency = latestData[0].currency;
  currencySelect.value = targetCurrency;
  }
  if (targetCurrency) {
  const history = await fetchHistory(targetCurrency, hours, days);
  renderChart(history, 'gram');
  } else {
  renderChart([], 'gram');
  }
  } catch (err) {
  console.error(err);
  document.getElementById('price-table-body').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Gagal memuat data. Coba refresh.</td></tr>';
  showToast('Gagal memuat data: ' + err.message);
  } finally {
  loadingTable.classList.add('d-none');
  loadingChart.classList.add('d-none');
  refreshBtn.disabled = false;
  currencySelect.disabled = false;
  rangeSelect.disabled = false;
  isLoading = false;
  }
  }

  document.getElementById('refresh-btn').addEventListener('click', loadAll);
  document.getElementById('currency-select').addEventListener('change', loadAll);
  document.getElementById('range-select').addEventListener('change', loadAll);
  document.addEventListener('DOMContentLoaded', () => {
  fetchCurrencies().then(loadAll).catch(err => showToast(err.message));
  });
  })();
</script>
@endpush

@push('styles')
<style>
  body {
    background-color: var(--tg-theme-bg-color, #f8f9fa); color: var(--tg-theme-text-color, #212529); }
    .card { background-color: var(--tg-theme-secondary-bg-color, #fff); border: none; border-radius: 1.25rem; }
    .card-header { background-color: var(--tg-theme-secondary-bg-color, #fff); border-bottom: 1px solid var(--tg-theme-section-separator-color, #dee2e6); }
    .table { color: var(--tg-theme-text-color, #212529); }
    .table thead th { background-color: var(--tg-theme-secondary-bg-color, #f8f9fa); border-color: var(--tg-theme-section-separator-color, #dee2e6); }
    .table-hover tbody tr:hover { background-color: rgba(241,196,15,0.05); }
    .form-select, .form-control { background-color: var(--tg-theme-bg-color, #fff); color: var(--tg-theme-text-color, #212529); border-color: var(--tg-theme-section-separator-color, #dee2e6); }
    .input-group-text { background-color: var(--tg-theme-bg-color, #fff); border-color: var(--tg-theme-section-separator-color, #dee2e6); color: var(--tg-theme-hint-color, #6c757d); }
    .btn-outline-secondary { border-color: var(--tg-theme-section-separator-color, #ced4da); color: var(--tg-theme-hint-color, #6c757d); }
    .btn-outline-secondary:hover { background-color: var(--tg-theme-secondary-bg-color, #e9ecef); color: var(--tg-theme-text-color, #495057); }
    .text-muted { color: var(--tg-theme-hint-color) !important; }
    @media (max-width: 768px) { .container { padding-left: 1rem; padding-right: 1rem; } .card-body { padding: 1rem; } .table td, .table th { font-size: 0.85rem; } }
    </style>
    @endpush