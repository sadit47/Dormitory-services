import { useEffect, useMemo, useState } from "react";
import { adminDashboardApi } from "./services/adminDashboardApi";
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  ResponsiveContainer,
  CartesianGrid,
} from "recharts";

function money(n: any) {
  return Number(n ?? 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

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

  useEffect(() => {
    load();
  }, []);

  const kpi = data?.data?.kpi ?? data?.kpi ?? {};

  const incomeByCategory = useMemo(() => {
    const income = data?.data?.income_categories ?? data?.income_categories ?? {};

    return [
      {
        label: "รายได้ทั้งหมด",
        value: Number(income.total ?? kpi?.income_total ?? 0),
      },
      {
        label: "รายได้ค่าเช่า",
        value: Number(income.rent ?? 0),
      },
      {
        label: "รายได้ค่าน้ำ/ค่าไฟ",
        value: Number(income.utility ?? 0),
      },
      {
        label: "รายได้ซ่อมแซม",
        value: Number(income.repair ?? 0),
      },
      {
        label: "รายได้ทำความสะอาด",
        value: Number(income.cleaning ?? 0),
      },
    ];
  }, [data, kpi]);

  if (loading) {
    return (
      <div className="flex h-56 items-center justify-center">
        <div className="rounded-2xl bg-white/90 px-5 py-3 text-slate-500 shadow-[0_10px_30px_rgba(15,23,42,0.06)]">
          กำลังโหลดข้อมูล...
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <section className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <div className="text-xs font-semibold tracking-wide text-slate-500">
              แอดมิน
            </div>
            <div className="mt-1 text-3xl font-bold tracking-tight text-slate-800">
              Dashboard
            </div>
            <div className="mt-1 text-sm text-slate-500">
              ภาพรวมของห้องพัก ผู้เช่า ห้องว่าง และรายได้ของระบบ
            </div>
          </div>

          <button
            onClick={load}
            className="h-10 rounded-2xl bg-linear-to-r from-slate-900 to-slate-700 px-4 text-sm font-medium text-white shadow-[0_10px_20px_rgba(15,23,42,0.16)] transition hover:-translate-y-0.5 hover:shadow-[0_14px_26px_rgba(15,23,42,0.20)]"
          >
            รีเฟรชข้อมูล
          </button>
        </div>
      </section>

      {/* KPI Cards */}
      <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 ">
        
        <KpiCard
          title="ห้องทั้งหมด"
          value={kpi?.rooms_total ?? 0}
          icon="🏢"
          accent="from-sky-100 to-cyan-100"
        />
        <KpiCard
          title="ผู้เช่าทั้งหมด"
          value={kpi?.tenant_count ?? 0}
          icon="👥"
          accent="from-violet-100 to-fuchsia-100"
        />
        <KpiCard
          title="ห้องว่าง"
          value={kpi?.rooms_vacant ?? 0}
          icon="🛏️"
          accent="from-amber-100 to-orange-100"
        />
        <KpiCard
          title="รายได้ทั้งหมด"
          value={`${money(kpi?.income_total ?? 0)} ฿`}
          icon="💰"
          accent="from-emerald-100 to-teal-100"
        />
      </section>

      {/* Charts */}
      <section className="grid grid-cols-1 gap-5 xl:grid-cols-2">
        {/* KPI overview chart */}

        {/* Income categories chart */}
        <div className="rounded-[28px] border border-white/70 bg-white/85 p-5 shadow-[0_14px_36px_rgba(15,23,42,0.06)] backdrop-blur-sm">
          <div className="mb-4">
            <div className="text-lg font-semibold text-slate-800">รายได้แยกตามหมวดหมู่</div>
            <div className="mt-1 text-sm text-slate-500">
              รายได้ทั้งหมด ค่าเช่า ค่าน้ำ/ค่าไฟ ซ่อมแซม และทำความสะอาด
            </div>
          </div>

          <div className="h-80 rounded-2xl bg-linear-to-b from-slate-50 to-white p-3">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={incomeByCategory}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                <XAxis dataKey="label" tick={{ fill: "#64748b", fontSize: 11 }} />
                <YAxis tick={{ fill: "#64748b", fontSize: 12 }} />
                <Tooltip
                  formatter={(value: any) => [`${money(value)} ฿`, "รายได้"]}
                  contentStyle={{
                    borderRadius: "16px",
                    border: "1px solid #e2e8f0",
                    boxShadow: "0 12px 30px rgba(15,23,42,0.08)",
                  }}
                />
                <Bar dataKey="value" radius={[10, 10, 0, 0]} fill="#10b981" />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </section>
    </div>
  );
}

function KpiCard({
  title,
  value,
  icon,
  accent,
}: {
  title: string;
  value: any;
  icon: string;
  accent: string;
}) {
  return (
    <div className="relative overflow-hidden rounded-3xl border border-white/80 bg-white/90 p-5 shadow-[0_12px_32px_rgba(15,23,42,0.05)] backdrop-blur-sm transition hover:-translate-y-0.5 hover:shadow-[0_16px_36px_rgba(15,23,42,0.08)]">
      <div className="relative z-10 flex items-start justify-between">
        <div>
          <div className="text-sm font-medium text-slate-500">{title}</div>
          <div className="mt-3 text-3xl font-bold tracking-tight text-slate-800">
            {value}
          </div>
        </div>

        <div
          className={`flex h-12 w-12 items-center justify-center rounded-2xl bg-linear-to-br ${accent} text-2xl shadow-inner`}
        >
          {icon}
        </div>
      </div>

      <div className="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-transparent via-slate-200/60 to-transparent" />
    </div>
  );
}