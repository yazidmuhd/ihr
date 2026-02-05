<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import ApplicantLayout from "@/Components/Layouts/ApplicantLayout.vue";

const props = defineProps({
    user: Object,
    profile: Object,
});

const form = useForm({
    name: props.profile?.name || "",
    headline: props.profile?.headline || "",
    phone: props.profile?.phone || "",
    location: props.profile?.location || "",
    skills_text: props.profile?.skills_text || "",
    linkedin_url: props.profile?.linkedin_url || "",
    github_url: props.profile?.github_url || "",
    portfolio_url: props.profile?.portfolio_url || "",
    website_url: props.profile?.website_url || "",
});

const saving = ref(false);
function save() {
    saving.value = true;
    form.put(route("applicant.profile.update"), {
        preserveScroll: true,
        onFinish: () => (saving.value = false),
    });
}

const displayName = computed(
    () => form.name || props.user?.name || props.user?.email,
);
const skillsList = computed(() =>
    (form.skills_text || "")
        .split(/[\n,]/g)
        .map((s) => s.trim())
        .filter(Boolean)
        .slice(0, 30),
);
</script>

<template>
    <Head title="Profile" />

    <ApplicantLayout :showSidebar="false" content-max="max-w-5xl">
        <!-- Page header card -->
        <section
            class="mb-5 rounded-2xl bg-white/80 p-5 ring-1 ring-white/50 backdrop-blur"
        >
            <div
                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">
                        Profile
                    </h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Keep your details up to date for better matches.
                    </p>
                </div>

                <button
                    class="btn-primary h-10 px-5"
                    :disabled="saving"
                    @click="save"
                >
                    {{ saving ? "Saving..." : "Save changes" }}
                </button>
            </div>
        </section>

        <!-- Form + Preview -->
        <div class="grid gap-6 md:grid-cols-12">
            <!-- Form -->
            <section
                class="rounded-2xl bg-white/80 p-5 ring-1 ring-white/50 backdrop-blur md:col-span-7"
            >
                <div class="grid gap-4">
                    <!-- Name + headline -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Full name</label>
                            <input v-model="form.name" class="field w-full" />
                            <div v-if="form.errors.name" class="err">
                                {{ form.errors.name }}
                            </div>
                        </div>
                        <div>
                            <label class="label">Headline (short pitch)</label>
                            <input
                                v-model="form.headline"
                                class="field w-full"
                                placeholder="e.g., Frontend Engineer • Vue & Laravel"
                            />
                            <div v-if="form.errors.headline" class="err">
                                {{ form.errors.headline }}
                            </div>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Phone</label>
                            <input
                                v-model="form.phone"
                                class="field w-full"
                                placeholder="+60..."
                            />
                            <div v-if="form.errors.phone" class="err">
                                {{ form.errors.phone }}
                            </div>
                        </div>
                        <div>
                            <label class="label">Location</label>
                            <input
                                v-model="form.location"
                                class="field w-full"
                                placeholder="Kuala Lumpur, MY"
                            />
                            <div v-if="form.errors.location" class="err">
                                {{ form.errors.location }}
                            </div>
                        </div>
                    </div>

                    <!-- Skills -->
                    <div>
                        <label class="label">Skills (comma or new line)</label>
                        <textarea
                            v-model="form.skills_text"
                            rows="4"
                            class="field w-full"
                            placeholder="Vue, Laravel, PostgreSQL, Tailwind, HRIS, ..."
                        ></textarea>
                        <div v-if="form.errors.skills_text" class="err">
                            {{ form.errors.skills_text }}
                        </div>
                    </div>

                    <!-- Links -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">LinkedIn</label>
                            <input
                                v-model="form.linkedin_url"
                                class="field w-full"
                                placeholder="https://www.linkedin.com/in/..."
                            />
                            <div v-if="form.errors.linkedin_url" class="err">
                                {{ form.errors.linkedin_url }}
                            </div>
                        </div>
                        <div>
                            <label class="label">GitHub</label>
                            <input
                                v-model="form.github_url"
                                class="field w-full"
                                placeholder="https://github.com/..."
                            />
                            <div v-if="form.errors.github_url" class="err">
                                {{ form.errors.github_url }}
                            </div>
                        </div>
                        <div>
                            <label class="label">Portfolio</label>
                            <input
                                v-model="form.portfolio_url"
                                class="field w-full"
                                placeholder="https://dribbble.com/..."
                            />
                            <div v-if="form.errors.portfolio_url" class="err">
                                {{ form.errors.portfolio_url }}
                            </div>
                        </div>
                        <div>
                            <label class="label">Website</label>
                            <input
                                v-model="form.website_url"
                                class="field w-full"
                                placeholder="https://..."
                            />
                            <div v-if="form.errors.website_url" class="err">
                                {{ form.errors.website_url }}
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            class="btn-ghost"
                            @click="form.reset()"
                        >
                            Reset
                        </button>
                        <button
                            type="button"
                            class="btn-primary"
                            :disabled="saving"
                            @click="save"
                        >
                            {{ saving ? "Saving..." : "Save changes" }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- Live Preview -->
            <aside
                class="rounded-2xl bg-white/70 p-5 ring-1 ring-white/40 backdrop-blur md:col-span-5"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">
                            {{ displayName }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-600">
                            {{
                                form.headline || "Your short pitch appears here"
                            }}
                        </p>
                        <p class="mt-1 text-sm text-slate-600">
                            <span v-if="form.location">{{
                                form.location
                            }}</span>
                            <span v-if="form.phone"> • {{ form.phone }}</span>
                        </p>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="muted mb-2">Skills preview</div>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="s in skillsList"
                            :key="s"
                            class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800"
                        >
                            {{ s }}
                        </span>
                        <span
                            v-if="!skillsList.length"
                            class="text-sm text-slate-500"
                            >Add skills to see chips here.</span
                        >
                    </div>
                </div>

                <div class="mt-5 grid gap-2">
                    <a
                        v-if="form.linkedin_url"
                        :href="form.linkedin_url"
                        target="_blank"
                        class="link-emerald"
                        >LinkedIn ↗</a
                    >
                    <a
                        v-if="form.github_url"
                        :href="form.github_url"
                        target="_blank"
                        class="link-emerald"
                        >GitHub ↗</a
                    >
                    <a
                        v-if="form.portfolio_url"
                        :href="form.portfolio_url"
                        target="_blank"
                        class="link-emerald"
                        >Portfolio ↗</a
                    >
                    <a
                        v-if="form.website_url"
                        :href="form.website_url"
                        target="_blank"
                        class="link-emerald"
                        >Website ↗</a
                    >
                </div>

                <!-- Flash status -->
                <div
                    v-if="$page.props.flash?.status"
                    class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800"
                >
                    {{ $page.props.flash.status }}
                </div>
            </aside>
        </div>
    </ApplicantLayout>
</template>

<style scoped>
.label {
    @apply mb-1 block text-sm font-medium text-slate-700;
}
.field {
    @apply rounded-xl border border-slate-300 bg-white px-3 py-2 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200;
}
.err {
    @apply mt-1 text-xs text-rose-600;
}

.btn-primary {
    @apply inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition;
    box-shadow: 0 8px 22px -10px rgba(16, 185, 129, 0.45);
}
.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 28px -14px rgba(16, 185, 129, 0.45);
}
.btn-ghost {
    @apply inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800 transition;
}
.btn-ghost:hover {
    transform: translateY(-1px);
    box-shadow: 0 18px 28px -16px rgba(2, 6, 23, 0.22);
}

.link-emerald {
    @apply font-medium text-emerald-700 hover:underline;
}
.muted {
    @apply text-xs uppercase tracking-wide text-slate-500;
}
</style>
