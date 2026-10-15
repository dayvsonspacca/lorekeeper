import type { Detail, Fact, FactWithSubject, SubjectRef } from './api'

export interface Page {
  value: <T = unknown>(type: string) => T | undefined
  values: <T = unknown>(type: string) => T[]
  facts: (type: string) => Fact[]
  linked: (type: string) => SubjectRef[]
  pointing: (type: string) => FactWithSubject[]
  updated: string | undefined
}

const unique = (refs: SubjectRef[]) =>
  refs.filter((ref, index) => refs.findIndex((other) => other.kind === ref.kind && other.id === ref.id) === index)

export function page(detail: Detail): Page {
  const facts = (type: string) => detail.facts.filter((fact) => fact.type === type)
  const latest = (list: Fact[]) => [...list].sort((a, b) => b.lastSeen.localeCompare(a.lastSeen))

  return {
    value: <T>(type: string) => latest(facts(type))[0]?.value as T | undefined,
    values: <T>(type: string) => facts(type).map((fact) => fact.value as T),
    facts,
    linked: (type) => unique(facts(type).flatMap((fact) => (fact.ref ? [fact.ref] : []))),
    pointing: (type) =>
      detail.references
        .filter((ref) => ref.type === type)
        .filter((ref, index, all) => all.findIndex((o) => o.subject.kind === ref.subject.kind && o.subject.id === ref.subject.id) === index),
    updated: detail.facts.map((fact) => fact.lastSeen).sort().at(-1),
  }
}
