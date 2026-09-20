"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { api } from "@/lib/api";

type Job = {
  id: number;
  title: string;
  overall_match: number | null;
  status: string;
  profile_gap: boolean;
  budget_min: number | null;
  budget_max: number | null;
};

export default function JobsPage() {
  const [jobs, setJobs] = useState<Job[]>([]);
  const [filter, setFilter] = useState("");

  useEffect(() => {
    const q = filter === "strong" ? "?strong=1" : filter ? `?status=${filter}` : "";
    api<{ data: Job[] }>(`/jobs${q}`).then((r) => setJobs(r.data || [])).catch(console.error);
  }, [filter]);

  return (
    <div className="space-y-6">
      <header className="flex items-end justify-between gap-4">
        <div>
          <h1 className="text-3xl font-semibold">Jobs</h1>
          <p className="text-zinc-400">Fetched via Upwork MCP, scored by AI against profile + portfolio.</p>
        </div>
        <select className="bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-sm" value={filter} onChange={(e) => setFilter(e.target.value)}>
          <option value="">All</option>
          <option value="strong">Strong matches</option>
          <option value="SHORTLISTED">Shortlisted</option>
          <option value="SKIPPED">Skipped</option>
          <option value="NEW">New</option>
        </select>
      </header>
      <div className="rounded-xl border border-zinc-800 overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-zinc-900 text-zinc-400 text-left">
            <tr>
              <th className="p-3">Match</th>
              <th className="p-3">Title</th>
              <th className="p-3">Budget</th>
              <th className="p-3">Status</th>
              <th className="p-3"></th>
            </tr>
          </thead>
          <tbody>
            {jobs.map((j) => (
              <tr key={j.id} className="border-t border-zinc-800">
                <td className="p-3 font-medium text-emerald-300">{j.overall_match ?? "—"}%</td>
                <td className="p-3">
                  {j.title}
                  {j.profile_gap && <span className="ml-2 text-xs text-amber-400">PROFILE GAP</span>}
                </td>
                <td className="p-3 text-zinc-400">{j.budget_min ?? "?"} – {j.budget_max ?? "?"}</td>
                <td className="p-3 text-zinc-400">{j.status}</td>
                <td className="p-3 text-right">
                  <Link href={`/jobs/${j.id}`} className="text-emerald-300 hover:underline">Open</Link>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
