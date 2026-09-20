"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";

export default function SchedulerPage() {
  const [runs, setRuns] = useState<any[]>([]);
  const [msg, setMsg] = useState("");

  function load() {
    api<any[]>("/scheduler/runs").then(setRuns).catch(console.error);
  }

  useEffect(() => {
    load();
  }, []);

  async function runNow() {
    setMsg("");
    try {
      await api("/scheduler/run", { method: "POST" });
      setMsg("Watcher queued");
      load();
    } catch (e) {
      setMsg(e instanceof Error ? e.message : "Failed");
    }
  }

  return (
    <div className="space-y-6">
      <header className="flex justify-between items-end">
        <div>
          <h1 className="text-3xl font-semibold">Scheduler</h1>
          <p className="text-zinc-400">Job watcher runs only when UPWORK_POLLING_ENABLED=true.</p>
        </div>
        <button onClick={runNow} className="rounded-lg bg-emerald-500 text-zinc-950 px-4 py-2 font-medium">Run watcher now</button>
      </header>
      {msg && <p className="text-sm text-amber-300">{msg}</p>}
      <div className="rounded-xl border border-zinc-800 overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-zinc-900 text-zinc-400 text-left">
            <tr>
              <th className="p-3">Job</th>
              <th className="p-3">Status</th>
              <th className="p-3">Started</th>
              <th className="p-3">Error</th>
            </tr>
          </thead>
          <tbody>
            {runs.map((r) => (
              <tr key={r.id} className="border-t border-zinc-800">
                <td className="p-3">{r.job_name}</td>
                <td className="p-3">{r.status}</td>
                <td className="p-3 text-zinc-400">{r.started_at}</td>
                <td className="p-3 text-red-400 text-xs">{r.error}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
