"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";

export default function PortfolioPage() {
  const [items, setItems] = useState<any[]>([]);
  const [title, setTitle] = useState("");
  const [notes, setNotes] = useState("");
  const [msg, setMsg] = useState("");

  function load() {
    api<{ data: any[] }>("/portfolio").then((r) => setItems(r.data || [])).catch(console.error);
  }

  useEffect(() => {
    load();
  }, []);

  async function create() {
    setMsg("");
    try {
      await api("/portfolio", {
        method: "POST",
        body: JSON.stringify({ title, raw_notes: notes, enrich: true }),
      });
      setTitle("");
      setNotes("");
      load();
      setMsg("Project saved (AI enrichment attempted).");
    } catch (e) {
      setMsg(e instanceof Error ? e.message : "Failed");
    }
  }

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-3xl font-semibold">Portfolio</h1>
        <p className="text-zinc-400">Upwork + internal projects. AI enrichment from free-text notes.</p>
      </header>

      <section className="border border-zinc-800 rounded-xl p-5 space-y-3">
        <h2 className="font-medium">Add internal project</h2>
        <input className="w-full bg-zinc-950 border border-zinc-700 rounded-lg px-3 py-2" placeholder="Title" value={title} onChange={(e) => setTitle(e.target.value)} />
        <textarea className="w-full min-h-28 bg-zinc-950 border border-zinc-700 rounded-lg px-3 py-2 text-sm" placeholder="Paste notes… AI will extract stack, capabilities, etc." value={notes} onChange={(e) => setNotes(e.target.value)} />
        <button onClick={create} className="rounded-lg bg-emerald-500 text-zinc-950 px-4 py-2 font-medium">Save + enrich</button>
        {msg && <p className="text-sm text-zinc-400">{msg}</p>}
      </section>

      <div className="grid md:grid-cols-2 gap-4">
        {items.map((p) => (
          <div key={p.id} className="border border-zinc-800 rounded-xl p-4">
            <div className="text-xs text-zinc-500">{p.source}</div>
            <div className="font-medium mt-1">{p.title}</div>
            <div className="text-sm text-zinc-400 mt-2 line-clamp-3">{p.description || p.raw_notes}</div>
            <div className="flex flex-wrap gap-2 mt-3">
              {(p.skills || []).slice(0, 6).map((s: any) => (
                <span key={s.id} className="text-xs rounded-full bg-zinc-900 border border-zinc-700 px-2 py-0.5">{s.skill}</span>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
