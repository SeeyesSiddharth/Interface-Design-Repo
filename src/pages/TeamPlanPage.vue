<script setup>
import PageHeader from '../components/PageHeader.vue'

const pageGroups = [
  {
    person: 'You',
    pages: 'Home + Team Plan',
    focus: 'Overall navigation, project framing, route map, final integration, video/report screenshots.',
  },
  {
    person: 'Teammate 1',
    pages: 'Catalogue + Game Detail',
    focus: 'Search, filter, sort, pagination, item cards, detailed requirements, likes, review entry points.',
  },
  {
    person: 'Teammate 2',
    pages: 'Compare + Requirements Guide',
    focus: 'Comparison table, educational content, requirement tiers, CPU/GPU/RAM/storage explanations.',
  },
  {
    person: 'Teammate 3',
    pages: 'Compatibility Checker + Reviews',
    focus: 'Reactive checker form, computed verdicts, community review display, social interaction polishing.',
  },
  {
    person: 'Teammate 4',
    pages: 'Login + Register',
    focus: 'Form validation, authenticated/unauthenticated visibility, error states, demo user flow.',
  },
  {
    person: 'Teammate 5',
    pages: 'Profile + Submit Game',
    focus: 'Member dashboard, create form, validation, protected member actions, local persistence checks.',
  },
  {
    person: 'Shared backup',
    pages: 'Admin Management + Not Found',
    focus: 'Edit/delete/reset workflows, CRUD demonstration, fallback route, final QA and mobile cleanup.',
  },
]
</script>

<template>
  <PageHeader
    title="Team Page Plan"
    text="A ready-made handover map so six people can each own at least two pages or a clear equivalent scope."
  />

  <section class="section-band">
    <div class="container">
      <div class="content-card p-3 mb-4">
        <h2 class="h4">Recommended split</h2>
        <p class="text-secondary mb-0">
          The app currently has 13 routes, plus reusable components and shared data. The table below gives every person
          a clear pickup point while keeping the highest-risk shared work visible.
        </p>
      </div>

      <div class="row g-3">
        <div v-for="group in pageGroups" :key="group.person" class="col-md-6 col-xl-4">
          <article class="content-card p-3 h-100">
            <p class="eyebrow mb-2">{{ group.person }}</p>
            <h2 class="h5">{{ group.pages }}</h2>
            <p class="text-secondary mb-0">{{ group.focus }}</p>
          </article>
        </div>
      </div>

      <div class="content-card p-3 mt-4">
        <h2 class="h4">What to improve next</h2>
        <ul class="mb-0">
          <li>Replace fictional seed data with researched real games if the team wants a real catalogue.</li>
          <li>Connect the store to a REST API and backend database for the final technical requirement.</li>
          <li>Add one advanced feature per student, such as saved builds, recommendation logic, charts, or accessibility tools.</li>
          <li>Capture screenshots from each owner page for the group report.</li>
          <li>Keep shared components small so teammates can work without merge conflicts.</li>
        </ul>
      </div>
    </div>
  </section>
</template>
