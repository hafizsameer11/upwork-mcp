"use client";

import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";
import { login } from "@/lib/api";

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState("manager@hmstech.local");
  const [password, setPassword] = useState("password");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError("");
    try {
      await login(email, password);
      router.push("/");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Login failed");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen bg-zinc-950 text-zinc-100 flex items-center justify-center p-6">
      <form onSubmit={onSubmit} className="w-full max-w-md border border-zinc-800 rounded-2xl p-8 space-y-4 bg-zinc-900/40">
        <div>
          <div className="text-xs uppercase tracking-[0.2em] text-emerald-400">HMS Tech</div>
          <h1 className="text-2xl font-semibold mt-1">Upwork BD Login</h1>
        </div>
        {error && <p className="text-sm text-red-400">{error}</p>}
        <label className="block text-sm">
          <span className="text-zinc-400">Email</span>
          <input className="mt-1 w-full rounded-lg bg-zinc-950 border border-zinc-700 px-3 py-2" value={email} onChange={(e) => setEmail(e.target.value)} />
        </label>
        <label className="block text-sm">
          <span className="text-zinc-400">Password</span>
          <input type="password" className="mt-1 w-full rounded-lg bg-zinc-950 border border-zinc-700 px-3 py-2" value={password} onChange={(e) => setPassword(e.target.value)} />
        </label>
        <button disabled={loading} className="w-full rounded-lg bg-emerald-500 text-zinc-950 font-medium py-2 disabled:opacity-50">
          {loading ? "Signing in…" : "Sign in"}
        </button>
        <p className="text-xs text-zinc-500">Seeded: manager@hmstech.local / password (manager) · bd@hmstech.local / password</p>
      </form>
    </div>
  );
}
