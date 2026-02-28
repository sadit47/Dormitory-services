import { useEffect, useState } from "react";
import { adminDashboardApi } from "./services/adminDashboardApi";
import {
  BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, CartesianGrid
} from "recharts";

export default function AdminDashboardPage() {
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  const load = async () => {
    setLoading(true);
    try {
      const res = await adminDashboardApi.summary();
      setData(res);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { load(); }, []);

  if (loading) return <div>Loading...</div>;

  const kpi = data?.data?.kpi ?? data?.kpi;
  const chart4 = data?.data?.chart_4 ?? data?.chart_4 ?? [];

  return (
    <div style={{ display: "grid", gap: 16 }}>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "end" }}>
        <div>
          <div style={{ fontSize: 13, color: "#6b7280", fontWeight: 700 }}>Admin</div>
          <div style={{ fontSize: 28, fontWeight: 900 }}>Dashboard</div>
        </div>
        <button
          onClick={load}
          style={{ padding: "10px 14px", borderRadius: 12, border: "1px solid #e5e7eb", background: "#fff", cursor: "pointer", fontWeight: 700 }}
        >
          Refresh
        </button>
      </div>

      {/* KPI Cards */}
      <div style={{ display: "grid", gridTemplateColumns: "repeat(4, minmax(0, 1fr))", gap: 12 }}>
        <KpiCard title="ห้องทั้งหมด" value={kpi?.rooms_total ?? 0} />
        <KpiCard title="ผู้เช่าทั้งหมด" value={kpi?.tenant_count ?? 0} />
        <KpiCard title="ห้องว่าง" value={kpi?.rooms_vacant ?? 0} />
        <KpiCard title="รายได้ทั้งหมด" value={`${Number(kpi?.income_total ?? 0).toFixed(2)} ฿`} />
      </div>

      {/* Chart 4 values */}
      <div style={{ background: "#fff", border: "1px solid #e5e7eb", borderRadius: 16, padding: 16 }}>
        <div style={{ fontWeight: 900, marginBottom: 10 }}>กราฟภาพรวม</div>
        <div style={{ height: 280 }}>
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={chart4}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="label" />
              <YAxis />
              <Tooltip />
              <Bar dataKey="value" />
            </BarChart>
          </ResponsiveContainer>
        </div>
      </div>
    </div>
  );
}

function KpiCard({ title, value }: { title: string; value: any }) {
  return (
    <div style={{ background: "#fff", border: "1px solid #e5e7eb", borderRadius: 16, padding: 14 }}>
      <div style={{ fontSize: 13, color: "#6b7280", fontWeight: 800 }}>{title}</div>
      <div style={{ fontSize: 28, fontWeight: 900, marginTop: 6 }}>{value}</div>
    </div>
  );
}
