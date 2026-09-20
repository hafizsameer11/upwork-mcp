"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";

export default function SearchProfilesPage() {
  const [rows, setRows] = useState<any[]>([]);

  useEffect(() => {
    api<any[]>("/search-profiles").then(setRows).catch(console.error);
  }, []);

  async function toggle(id: number, is_enabled: boolean) {
    await api(`/search-profiles/${id}`, { method: "PATCH", body: JSON.stringify({ is_enabled: !is_enabled }) });
    setRows(await api("/search-profiles"));
  }

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-3xl font-semibold">Search Profiles</h1>
        <p className="text-zinc-400">DB-driven watchers — change strategy without code.</p>
      </header>
      <div className="space-y-3">
        {rows.map((p) => (
          <div key={p.id} className="border border-zinc-800 rounded-xl p-4 flex justify-between gap-4">
            <div>
              <div className="font-medium">{p.name}</div>
              <div className="text-sm text-zinc-500 mt-1">
                Include: {(p.include_keywords || []).join(", ")}
              </div>
              <div className="text-sm text-zinc-500">Min budget ${p.min_budget} · Age &lt; {p.max_job_age_hours}h · Alert ≥ {p.alert_threshold}%</div>
            </div>
            <button onClick={() => toggle(p.id, p.is_enabled)} className={`h-fit rounded-lg px-3 py-1.5 text-sm ${p.is_enabled ? "bg-emerald-500/20 text-emerald-300" : "bg-zinc-800 text-zinc-400"}`}>
              {p.is_enabled ? "ON" : "OFF"}
            </button>
          </div>
        ))}
      </div>
    </div>
  );
}
