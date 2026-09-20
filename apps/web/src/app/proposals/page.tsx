"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { api } from "@/lib/api";

export default function ProposalsPage() {
  const [status, setStatus] = useState("");
  const [rows, setRows] = useState<any[]>([]);

  useEffect(() => {
    const q = status ? `?status=${status}` : "";
    api<{ data: any[] }>(`/proposals${q}`).then((r) => setRows(r.data || [])).catch(console.error);
  }, [status]);

  return (
    <div className="space-y-6">
      <header className="flex justify-between items-end">
        <div>
          <h1 className="text-3xl font-semibold">Proposals</h1>
          <p className="text-zinc-400">Drafts → approval → gated MCP submit → AI review.</p>
        </div>
        <select className="bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-sm" value={status} onChange={(e) => setStatus(e.target.value)}>
          <option value="">All</option>
          <option value="DRAFT">Drafts</option>
          <option value="AWAITING_APPROVAL">Awaiting Approval</option>
          <option value="APPROVED">Approved</option>
          <option value="SUBMITTED">Submitted</option>
        </select>
      </header>
      <div className="space-y-3">
        {rows.map((p) => (
          <Link key={p.id} href={`/proposals/${p.id}`} className="block border border-zinc-800 rounded-xl p-4 hover:bg-zinc-900/50">
            <div className="flex justify-between gap-4">
              <div>
                <div className="font-medium">{p.job?.title || `Proposal #${p.id}`}</div>
                <div className="text-sm text-zinc-500 mt-1">{p.ai_strategy || "no strategy"} · {p.status}</div>
              </div>
              <div className="text-sm text-zinc-400">{p.proposed_rate ? `$${p.proposed_rate}` : "—"}</div>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}
