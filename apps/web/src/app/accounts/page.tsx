"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";

export default function AccountsPage() {
  const [rows, setRows] = useState<any[]>([]);
  const [label, setLabel] = useState("");
  const [token, setToken] = useState("");

  function load() {
    api<any[]>("/accounts").then(setRows).catch(console.error);
  }

  useEffect(() => {
    load();
  }, []);

  async function create() {
    await api("/accounts", { method: "POST", body: JSON.stringify({ label, is_default: rows.length === 0 }) });
    setLabel("");
    load();
  }

  async function connect(id: number) {
    await api(`/accounts/${id}/connect`, {
      method: "POST",
      body: JSON.stringify({ access_token: token }),
    });
    setToken("");
    load();
  }

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-3xl font-semibold">Upwork Accounts</h1>
        <p className="text-zinc-400">Multi-account / agency. Paste MCP OAuth access token after browser auth.</p>
      </header>

      <div className="border border-zinc-800 rounded-xl p-5 space-y-3">
        <input className="w-full bg-zinc-950 border border-zinc-700 rounded-lg px-3 py-2" placeholder="Account label" value={label} onChange={(e) => setLabel(e.target.value)} />
        <button onClick={create} className="rounded-lg bg-emerald-500 text-zinc-950 px-4 py-2 font-medium">Add account</button>
      </div>

      <div className="border border-zinc-800 rounded-xl p-5 space-y-3">
        <h2 className="font-medium">Connect token</h2>
        <textarea className="w-full min-h-24 bg-zinc-950 border border-zinc-700 rounded-lg px-3 py-2 text-sm" placeholder="Paste access token from Upwork MCP OAuth" value={token} onChange={(e) => setToken(e.target.value)} />
        {rows.map((a) => (
          <div key={a.id} className="flex justify-between items-center border border-zinc-800 rounded-lg p-3">
            <div>
              <div className="font-medium">{a.label}</div>
              <div className="text-xs text-zinc-500">{a.is_active ? "active" : "inactive"} · {a.is_connected ? "connected" : "needs login"}</div>
            </div>
            <div className="flex gap-2">
              <button onClick={() => connect(a.id)} className="text-sm rounded-lg border border-zinc-700 px-3 py-1.5">Connect token</button>
              <button onClick={() => api(`/accounts/${a.id}/sync`, { method: "POST" })} className="text-sm rounded-lg border border-zinc-700 px-3 py-1.5">Sync</button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
