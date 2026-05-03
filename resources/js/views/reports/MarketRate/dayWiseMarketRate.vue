<template>
  <div class="container-fluid">
    <breadcrumb :options="['DayWiseMarketRateReport']" />

    <div class="card">
      <div class="card-header">
        <span>Day Wise Market Rate Report</span>
      </div>
      <div class="card-body">

        <!-- Filter Row -->
        <div class="row mb-2">
          <div class="col-md-3">
            <label>Date</label>
            <input type="date" class="form-control" v-model="selectedDate" @change="fetchReport" />
          </div>
          <div class="col-md-3" style="display:flex; align-items:flex-end; gap:5px;">
            <button class="btn btn-success btn-sm" @click="exportExcel">Export Excel</button>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="text-center py-4">
          <skeleton-loader :row="4" />
        </div>

        <!-- Report Tables -->
        <div v-else-if="Object.keys(reportData).length > 0">
          <div class="table-condensed">
            <div class="regions-wrap">
              <div class="region-block" v-for="(regionData, regionName) in reportData" :key="regionName">
                <table class="table table-bordered table-striped dt-responsive nowrap dataTable no-footer dtr-inline table-sm rate-table">
                  <thead>
                  <tr>
                    <td class="card-header"><strong>Region</strong></td>
                    <td class="card-header" colspan="4"><strong>Date</strong></td>
                  </tr>
                  <tr>
                    <td><strong>{{ regionData.products[0].RegionName }}</strong></td>
                    <td colspan="4">{{ formattedDate }}</td>
                  </tr>
                  <tr>
                    <td colspan="5" class="card-header text-center">
                      {{ regionData.products[0].RegionName }} Market Rate of {{ regionData.products.length }} Products
                    </td>
                  </tr>
                  <tr class="thead-dark">
                    <th>Product</th>
                    <th>C.Rate</th>
                    <th>M.Rate</th>
                    <th>Diff</th>
                    <th>% Of U.Rate</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr v-for="(product, idx) in regionData.products" :key="idx">
                    <td class="td-product">{{ product.ProductName }}</td>
                    <td class="td-num">{{ product.CRate }}</td>
                    <td class="td-num td-mrate">{{ product.MRate }}</td>
                    <td class="td-num" :class="parseFloat(product.Diff) < 0 ? 'td-neg' : ''">{{ product.Diff }}</td>
                    <td class="td-num">{{ parseFloat(product.PercentURate).toFixed(2) }}</td>
                  </tr>
                  </tbody>
                  <tfoot>
                  <tr class="avg-row">
                    <td><strong>Average</strong></td>
                    <td>{{ regionData.average.CRate }}</td>
                    <td>{{ regionData.average.MRate }}</td>
                    <td>{{ regionData.average.Diff }}</td>
                    <td>{{ regionData.average.PercentURate }}</td>
                  </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty -->
        <div v-else class="text-center py-4 text-muted">
          No data found for {{ formattedDate }}.
        </div>

      </div>
    </div>
  </div>
</template>

<script>
import { Common } from "../../../mixins/common";
import moment from "moment";

const XLSX = require('xlsx-js-style');

export default {
  mixins: [Common],

  data() {
    return {
      isLoading: false,
      selectedDate: new Date().toISOString().split('T')[0],
      reportData: {}
    };
  },

  computed: {
    formattedDate() {
      return moment(this.selectedDate).format('DD-MMM-YY');
    }
  },

  mounted() {
    this.fetchReport();
  },

  methods: {
    fetchReport() {
      this.isLoading = true;
      this.axiosGet('report/market-rate?date=' + this.selectedDate, (response) => {
        this.reportData = response.regions;
        this.isLoading = false;
      }, (error) => {
        console.error(error);
        this.isLoading = false;
      });
    },

    exportExcel() {
      const regionKeys = Object.keys(this.reportData);
      if (!regionKeys.length) return;

      const COLS = 6;
      const maxProducts = Math.max.apply(null, regionKeys.map(function(k) {
        return this.reportData[k].products.length;
      }.bind(this)));

      var mkCell = function(value, bg, color, bold, align) {
        bg    = bg    || 'FFFFFF';
        color = color || '000000';
        bold  = bold  || false;
        align = align || 'left';

        var isNum = typeof value === 'number' ||
            (typeof value === 'string' && value !== '' && !isNaN(value));

        return {
          v: isNum && typeof value === 'string' ? parseFloat(value) : value,
          t: isNum ? 'n' : 's',
          s: {
            fill:      { fgColor: { rgb: bg } },
            font:      { name: 'Arial', sz: 9, bold: bold, color: { rgb: color } },
            alignment: { horizontal: align, vertical: 'center' },
            border: {
              top:    { style: 'thin', color: { rgb: 'CCCCCC' } },
              bottom: { style: 'thin', color: { rgb: 'CCCCCC' } },
              left:   { style: 'thin', color: { rgb: 'CCCCCC' } },
              right:  { style: 'thin', color: { rgb: 'CCCCCC' } }
            }
          }
        };
      };

      var empty = function(bg) { return mkCell('', bg || 'FFFFFF'); };

      var rows = [];

      // ── Row 1: Region / Date label row ──
      var r1 = [];
      regionKeys.forEach(function() {
        r1.push(mkCell('Region', 'FFD700', '000000', true, 'left'));
        r1.push(mkCell('Date',   'FFD700', '000000', true, 'left'));
        r1.push(empty('FFD700'));
        r1.push(empty('FFD700'));
        r1.push(empty('FFD700'));
        r1.push(empty('FFFFFF')); // spacer
      });
      rows.push(r1);

      // ── Row 2: Region name / Date value ──
      var r2 = [];
      regionKeys.forEach(function(key) {
        var regionName = this.reportData[key].products[0]
            ? this.reportData[key].products[0].RegionName
            : key;
        r2.push(mkCell(regionName,        'FFD700', '000000', true,  'left'));
        r2.push(mkCell(this.formattedDate,'FFD700', '000000', false, 'left'));
        r2.push(empty('FFFFFF'));
        r2.push(empty('FFFFFF'));
        r2.push(empty('FFFFFF'));
        r2.push(empty('FFFFFF'));
      }.bind(this));
      rows.push(r2);

      // ── Row 3: Subtitle ──
      var r3 = [];
      regionKeys.forEach(function(key) {
        var rd         = this.reportData[key];
        var regionName = rd.products[0] ? rd.products[0].RegionName : key;
        var title      = regionName + ' Market Rate of ' + rd.products.length + ' Products';
        r3.push(mkCell(title, '343A40', 'FFFFFF', true, 'center'));
        r3.push(mkCell('', '343A40'));
        r3.push(mkCell('', '343A40'));
        r3.push(mkCell('', '343A40'));
        r3.push(mkCell('', '343A40'));
        r3.push(empty('FFFFFF'));
      }.bind(this));
      rows.push(r3);

      // ── Row 4: Column headers ──
      var r4 = [];
      regionKeys.forEach(function() {
        r4.push(mkCell('Product',     'FFD700', '000000', true, 'left'));
        r4.push(mkCell('C.Rate',      'FFD700', '000000', true, 'right'));
        r4.push(mkCell('M.Rate',      'FFD700', '000000', true, 'right'));
        r4.push(mkCell('Diff',        'FFD700', '000000', true, 'right'));
        r4.push(mkCell('% Of U.Rate', 'FFD700', '000000', true, 'right'));
        r4.push(empty('FFFFFF'));
      });
      rows.push(r4);

      // ── Product data rows ──
      for (var i = 0; i < maxProducts; i++) {
        var bg  = i % 2 === 0 ? 'FFFFFF' : 'F7F7F7';
        var row = [];
        regionKeys.forEach(function(key) {
          var p = this.reportData[key].products[i];
          if (p) {
            var diff = parseFloat(p.Diff);
            row.push(mkCell(p.ProductName,          bg, '000000', false, 'left'));
            row.push(mkCell(parseFloat(p.CRate),    bg, '000000', false, 'right'));
            row.push(mkCell(parseFloat(p.MRate),    bg, '008000', true,  'right'));
            row.push(mkCell(parseFloat(p.Diff),     bg, diff < 0 ? 'CC0000' : '000000', false, 'right'));
            row.push(mkCell(parseFloat(p.PercentURate), bg, '000000', false, 'right'));
          } else {
            row.push(empty(bg)); row.push(empty(bg)); row.push(empty(bg));
            row.push(empty(bg)); row.push(empty(bg));
          }
          row.push(empty('FFFFFF'));
        }.bind(this));
        rows.push(row);
      }

      // ── Average row ──
      var avgRow = [];
      regionKeys.forEach(function(key) {
        var avg = this.reportData[key].average;
        avgRow.push(mkCell('Average',                   'FF8C00', 'FFFFFF', true, 'left'));
        avgRow.push(mkCell(parseFloat(avg.CRate),       'FF8C00', 'FFFFFF', true, 'right'));
        avgRow.push(mkCell(parseFloat(avg.MRate),       'FF8C00', 'FFFFFF', true, 'right'));
        avgRow.push(mkCell(parseFloat(avg.Diff),        'FF8C00', 'FFFFFF', true, 'right'));
        avgRow.push(mkCell(parseFloat(avg.PercentURate),'FF8C00', 'FFFFFF', true, 'right'));
        avgRow.push(empty('FFFFFF'));
      }.bind(this));
      rows.push(avgRow);

      // ── Build worksheet ──
      var ws = XLSX.utils.aoa_to_sheet(rows);

      // Merge subtitle row per region
      ws['!merges'] = [];
      regionKeys.forEach(function(key, i) {
        var startCol = i * COLS;
        ws['!merges'].push({
          s: { r: 2, c: startCol },
          e: { r: 2, c: startCol + 4 }
        });
      });

      // Column widths
      ws['!cols'] = [];
      regionKeys.forEach(function(_, i) {
        var base = i * COLS;
        ws['!cols'][base]     = { wch: 22 };
        ws['!cols'][base + 1] = { wch: 9  };
        ws['!cols'][base + 2] = { wch: 9  };
        ws['!cols'][base + 3] = { wch: 8  };
        ws['!cols'][base + 4] = { wch: 10 };
        ws['!cols'][base + 5] = { wch: 2  };
      });

      // Row heights
      ws['!rows'] = [
        { hpt: 15 }, { hpt: 15 }, { hpt: 15 }, { hpt: 15 }
      ];

      var wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Market Rate');
      XLSX.writeFile(wb, 'market-rate-' + this.selectedDate + '.xlsx');
    }
  }
};
</script>

<style scoped>
.regions-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: flex-start;
}

.region-block {
  flex: 1 1 260px;
  min-width: 220px;
  overflow-x: auto;
}

th { font-size: 13px; }
td { font-size: 13px; }

.rate-table td,
.rate-table th {
  padding: 3px 5px !important;
  white-space: nowrap;
}

.hdr-yellow {
  background-color: #FFD700;
  font-size: 13px;
}

.hdr-subtitle {
  background-color: #343a40;
  color: #fff;
  font-weight: bold;
  text-align: center;
  font-size: 12px;
}

.td-product {
  text-align: left;
  max-width: 140px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.td-num   { text-align: right; }
.td-mrate { color: #008000; font-weight: bold; }
.td-neg   { color: #cc0000; }

.avg-row td {
  background-color: #FF8C00;
  color: #fff;
  text-align: right;
  font-size: 13px;
}
.avg-row td:first-child { text-align: left; }
</style>