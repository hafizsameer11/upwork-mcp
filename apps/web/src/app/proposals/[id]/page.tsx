"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { api } from "@/lib/api";

export default function ProposalDetailPage() {
  const { id } = useParams<{ id: string }>();
  const [p, setP] = useState<any>(null);
  const [letter, setLetter] = useState("");
  const [msg, setMsg] = useState("");
  const [busy, setBusy] = useState("");

  async function reload() {
    const data = await api<any>(`/proposals/${id}`);
    setP(data);
    setLetter(data.cover_letter || "");
  }

  useEffect(() => {
    reload().catch((e) => setMsg(e.message));
  }, [id]);

  async function run(action: string, path: string, body?: object) {
    setBusy(action);
    setMsg("");
    try {
      await api(path, { method: "POST", body: body ? JSON.stringify(body) : undefined });
      await reload();
      setMsg(`${action} ok`);
    } catch (e) {
      setMsg(e instanceof Error ? e.message : "Failed");
    } finally {
      setBusy("");
    }
  }

  if (!p) return <p className="text-zinc-500">{msg || "Loading…"}</p>;

  const review = p.aiReview || p.ai_review;

  return (
    <div className="space-y-6 max-w-4xl">
      <header>
        <div className="text-sm text-zinc-500">{p.status}</div>
        <h1 className="text-3xl font-semibold">{p.job?.title}</h1>
        <p className="text-zinc-400 mt-1">Strategy: {p.ai_strategy || "—"} · Rate: {p.proposed_rate ?? "—"} · Connects: {p.proposal_connects ?? "—"}</p>
      </header>

      <section className="border border-zinc-800 rounded-xl p-5 space-y-3">
        <h2 className="font-medium">Strategies</h2>
        <div className="grid gap-3">
          {(p.versions || []).map((v: any) => (
            <button
              key={v.id}
              className={`text-left rounded-lg border p-3 ${v.is_selected ? "border-emerald-500 bg-emerald-500/10" : "border-zinc-800"}`}
              onClick={() => api(`/proposals/${id}`, { method: "PATCH", body: JSON.stringify({ selected_version_id: v.id }) }).then(reload)}
            >
              <div className="text-sm font-medium capitalize">{v.ai_strategy}</div>
              <div className="text-xs text-zinc-500 mt-1 line-clamp-2">{v.cover_letter}</div>
            </button>
          ))}
        </div>
      </section>

      <section className="border border-zinc-800 rounded-xl p-5 space-y-3">
        <h2 className="font-medium">Cover letter</h2>
        <textarea className="w-full min-h-48 bg-zinc-950 border border-zinc-700 rounded-lg p-3 text-sm" value={letter} onChange={(e) => setLetter(e.target.value)} />
        <button
          className="rounded-lg border border-zinc-700 px-3 py-2 text-sm"
          onClick={() => api(`/proposals/${id}`, { method: "PATCH", body: JSON.stringify({ cover_letter: letter }) }).then(reload)}
        >
          Save edits
        </button>
      </section>

      <section className="border border-zinc-800 rounded-xl p-5">
        <h2 className="font-medium mb-3">Portfolio evidence</h2>
        <ul className="space-y-2 text-sm">
          {(p.portfolios || []).map((pp: any) => (
            <li key={pp.id}>☑ {pp.project?.title}</li>
          ))}
        </ul>
      </section>

      {review && (
        <section className="border border-amber-500/30 bg-amber-500/5 rounded-xl p-5 space-y-3">
          <h2 className="font-medium">AI guidance on submitted proposal · {review.quality_score}/100</h2>
          <p className="text-sm text-zinc-300">{review.summary}</p>
          <div className="grid md:grid-cols-2 gap-4 text-sm">
            <div>
              <div className="text-zinc-500 mb-1">Strengths</div>
              <ul className="list-disc pl-5">{(review.strengths || []).map((x: string, i: number) => <li key={i}>{x}</li>)}</ul>
            </div>
            <div>
              <div className="text-zinc-500 mb-1">Weaknesses</div>
              <ul className="list-disc pl-5">{(review.weaknesses || []).map((x: string, i: number) => <li key={i}>{x}</li>)}</ul>
            </div>
          </div>
          <div>
            <div className="text-zinc-500 mb-1 text-sm">Improve next time</div>
            <ul className="list-disc pl-5 text-sm">{(review.suggestions || []).map((x: string, i: number) => <li key={i}>{x}</li>)}</ul>
          </div>
        </section>
      )}

      {msg && <p className="text-sm text-zinc-400">{msg}</p>}

      <div className="flex flex-wrap gap-3">
        {p.status === "DRAFT" && (
          <button disabled={!!busy} onClick={() => run("submit-for-approval", `/proposals/${id}/submit-for-approval`)} className="rounded-lg bg-emerald-500 text-zinc-950 px-4 py-2 font-medium">
            Submit for approval
          </button>
        )}
        {p.status === "AWAITING_APPROVAL" && (
          <>
            <button disabled={!!busy} onClick={() => run("approve", `/proposals/${id}/approve`)} className="rounded-lg bg-emerald-500 text-zinc-950 px-4 py-2 font-medium">Approve</button>
            <button disabled={!!busy} onClick={() => run("reject", `/proposals/${id}/reject`)} className="rounded-lg border border-zinc-700 px-4 py-2">Reject</button>
          </>
        )}
        {p.status === "APPROVED" && (
          <button disabled={!!busy} onClick={() => run("submit", `/proposals/${id}/submit`)} className="rounded-lg bg-emerald-500 text-zinc-950 px-4 py-2 font-medium">
            Submit via Upwork MCP (gated)
          </button>
        )}
        {p.status === "SUBMITTED" && (
          <>
            <button disabled={!!busy} onClick={() => run("reanalyze", `/proposals/${id}/reanalyze`)} className="rounded-lg border border-zinc-700 px-4 py-2">Re-analyze AI review</button>
            <button disabled={!!busy} onClick={() => run("outcome", `/proposals/${id}/outcome`, { outcome: "reply" })} className="rounded-lg border border-zinc-700 px-4 py-2">Mark reply</button>
            <button disabled={!!busy} onClick={() => run("outcome", `/proposals/${id}/outcome`, { outcome: "hired" })} className="rounded-lg border border-zinc-700 px-4 py-2">Mark hired</button>
          </>
        )}
      </div>
    </div>
  );
}
