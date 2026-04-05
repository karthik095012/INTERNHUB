// ================================================================
//  data.js  —  InternHub LocalStorage Database (with Reliability)
//  Tables: users, internships, applications, resumes
//  Features: Data validation, error handling, backup/restore
// ================================================================

/* ── Data Backup & Recovery ───────────────────────────────── */
const DataBackup = {
  autoBackup() {
    try {
      const backup = {
        users: localStorage.getItem('ih_users'),
        jobs: localStorage.getItem('ih_jobs'),
        apps: localStorage.getItem('ih_apps'),
        resumes: localStorage.getItem('ih_resumes'),
        timestamp: new Date().toISOString()
      };
      localStorage.setItem('ih_backup_latest', JSON.stringify(backup));
    } catch(e) {
      console.warn('Backup failed:', e);
    }
  },

  restore() {
    try {
      const backup = JSON.parse(localStorage.getItem('ih_backup_latest') || 'null');
      if (!backup) { console.log('No backup found'); return false; }
      Object.entries(backup).forEach(([k, v]) => {
        if (k !== 'timestamp' && v) localStorage.setItem(k, v);
      });
      console.log('Data restored from backup');
      return true;
    } catch(e) {
      console.error('Restore failed:', e);
      return false;
    }
  }
};

/* ── Input Validation ──────────────────────────────────────– */
const Validate = {
  email(e) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e); },
  password(p) { return p && p.length >= 6; },
  name(n) { return n && n.trim().length >= 2; },
  noXSS(str) { return /^[a-zA-Z0-9\s\-.,@]*$/.test(str); },
  safeJSON(str) {
    try { JSON.parse(str); return true; } catch(e) { return false; }
  }
};

// ── Seed internships (posted by system/recruiter) ─────────────
const SEED_JOBS = [
  { id:"j1", recruiterId:"r_seed", title:"Frontend Developer Intern", company:"Vercel", location:"Remote", stipend:"₹25,000/mo", skills:["React","JavaScript","CSS"], duration:"3 months", deadline:"2026-08-30", logo:"V", color:"#111111", description:"Work with the frontend platform team building developer-facing UI. Contribute to real product features used by millions worldwide.", postedAt:"2026-03-01T10:00:00Z", active:true },
  { id:"j2", recruiterId:"r_seed", title:"Data Science Intern", company:"Flipkart", location:"Bengaluru", stipend:"₹30,000/mo", skills:["Python","ML","SQL"], duration:"6 months", deadline:"2026-07-15", logo:"F", color:"#F5A623", description:"Analyze large-scale e-commerce data to derive insights. Work on recommendation systems and demand forecasting with product teams.", postedAt:"2026-03-02T09:00:00Z", active:true },
  { id:"j3", recruiterId:"r_seed", title:"Backend Engineering Intern", company:"Razorpay", location:"Bengaluru", stipend:"₹35,000/mo", skills:["Node.js","Go","PostgreSQL"], duration:"4 months", deadline:"2026-08-10", logo:"R", color:"#2D9CDB", description:"Build scalable APIs and microservices powering India's leading payments infrastructure. High-throughput distributed systems.", postedAt:"2026-03-03T11:00:00Z", active:true },
  { id:"j4", recruiterId:"r_seed", title:"UI/UX Design Intern", company:"Swiggy", location:"Hyderabad", stipend:"₹20,000/mo", skills:["Figma","Prototyping","User Research"], duration:"3 months", deadline:"2026-07-20", logo:"S", color:"#FC8019", description:"Design delightful consumer experiences for food delivery at scale. Conduct research, create wireframes, and collaborate with engineers.", postedAt:"2026-03-04T08:00:00Z", active:true },
  { id:"j5", recruiterId:"r_seed", title:"Machine Learning Intern", company:"CRED", location:"Remote", stipend:"₹40,000/mo", skills:["Python","TensorFlow","NLP"], duration:"6 months", deadline:"2026-09-01", logo:"C", color:"#1A1A2E", description:"Work on ML models for credit risk, fraud detection and personalisation. Access to large financial datasets.", postedAt:"2026-03-05T14:00:00Z", active:true },
  { id:"j6", recruiterId:"r_seed", title:"DevOps Intern", company:"Zepto", location:"Mumbai", stipend:"₹22,000/mo", skills:["Docker","Kubernetes","AWS"], duration:"3 months", deadline:"2026-07-25", logo:"Z", color:"#6C5CE7", description:"Support infrastructure automation and CI/CD pipelines at one of India's fastest-growing quick-commerce startups.", postedAt:"2026-03-06T10:00:00Z", active:true },
];

// ── DB Core ──────────────────────────────────────────────────
const DB = {

  // ── USERS ──────────────────────────────────────────────────
  getUsers() {
    try {
      const u = localStorage.getItem("ih_users");
      return u && Validate.safeJSON(u) ? JSON.parse(u) : [];
    } catch(e) {
      console.error('getUsers error:', e);
      DataBackup.restore();
      return [];
    }
  },

  saveUsers(u) {
    try {
      localStorage.setItem("ih_users", JSON.stringify(u));
      DataBackup.autoBackup();
    } catch(e) {
      console.error('saveUsers error:', e);
    }
  },

  findUserByEmail(email) {
    try {
      if (!Validate.email(email)) return null;
      return this.getUsers().find(u => u.email.toLowerCase() === email.toLowerCase().trim());
    } catch(e) {
      console.warn('findUserByEmail error:', e);
      return null;
    }
  },

  findUserById(id) {
    try {
      return id ? this.getUsers().find(u => u.id === id) : null;
    } catch(e) {
      console.warn('findUserById error:', e);
      return null;
    }
  },

  createUser({ name, email, password, role, company="" }) {
    try {
      if (!Validate.name(name)) return { ok:false, msg:"Name must be at least 2 characters" };
      if (!Validate.email(email)) return { ok:false, msg:"Invalid email address" };
      if (!Validate.password(password)) return { ok:false, msg:"Password must be at least 6 characters" };
      if (this.findUserByEmail(email)) return { ok:false, msg:"Email already registered." };
      
      const users = this.getUsers();
      const user  = {
        id:        (role==="recruiter"?"r_":"s_") + Date.now(),
        name:      name.trim(),
        email:     email.toLowerCase().trim(),
        password,
        role,
        company:   company.trim(),
        joinedAt:  new Date().toISOString(),
      };
      users.push(user);
      this.saveUsers(users);
      return { ok:true, user };
    } catch(e) {
      console.error('createUser error:', e);
      return { ok:false, msg:"Failed to create user" };
    }
  },

  verifyUser(email, password) {
    try {
      if (!Validate.email(email) || !password) return null;
      const user = this.findUserByEmail(email);
      if (!user || user.password !== password) return null;
      return user;
    } catch(e) {
      console.warn('verifyUser error:', e);
      return null;
    }
  },

  // ── SESSION ────────────────────────────────────────────────
  getSession() {
    try {
      const s = sessionStorage.getItem("ih_session");
      return s && Validate.safeJSON(s) ? JSON.parse(s) : null;
    } catch(e) {
      console.warn('getSession error:', e);
      return null;
    }
  },

  setSession(u) {
    try {
      const safe = { ...u }; delete safe.password;
      sessionStorage.setItem("ih_session", JSON.stringify(safe));
    } catch(e) {
      console.error('setSession error:', e);
    }
  },

  clearSession() {
    try { sessionStorage.removeItem("ih_session"); } 
    catch(e) { console.warn('clearSession error:', e); }
  },

  // ── INTERNSHIPS / JOBS ─────────────────────────────────────
  getJobs() {
    try {
      let jobs = localStorage.getItem("ih_jobs");
      if (!jobs || !Validate.safeJSON(jobs)) {
        localStorage.setItem("ih_jobs", JSON.stringify(SEED_JOBS));
        return SEED_JOBS;
      }
      return JSON.parse(jobs);
    } catch(e) {
      console.error('getJobs error:', e);
      return SEED_JOBS;
    }
  },

  saveJobs(jobs) {
    try {
      localStorage.setItem("ih_jobs", JSON.stringify(jobs));
      DataBackup.autoBackup();
    } catch(e) {
      console.error('saveJobs error:', e);
    }
  },

  getJobById(id) {
    try {
      return id ? this.getJobs().find(j => j.id === id) : null;
    } catch(e) {
      console.warn('getJobById error:', e);
      return null;
    }
  },

  getJobsByRecruiter(rId) {
    try {
      return rId ? this.getJobs().filter(j => j.recruiterId === rId) : [];
    } catch(e) {
      console.warn('getJobsByRecruiter error:', e);
      return [];
    }
  },

  getActiveJobs() {
    try { return this.getJobs().filter(j => j && j.active); } 
    catch(e) { console.warn('getActiveJobs error:', e); return []; }
  },

  createJob(data) {
    try {
      const jobs = this.getJobs();
      const job  = { ...data, id:"j_"+Date.now(), postedAt:new Date().toISOString(), active:true };
      jobs.push(job);
      this.saveJobs(jobs);
      return job;
    } catch(e) {
      console.error('createJob error:', e);
      return null;
    }
  },

  updateJob(id, updates) {
    try {
      const jobs = this.getJobs().map(j => j.id === id ? { ...j, ...updates } : j);
      this.saveJobs(jobs);
    } catch(e) {
      console.error('updateJob error:', e);
    }
  },

  deleteJob(id) {
    try {
      this.saveJobs(this.getJobs().filter(j => j.id !== id));
    } catch(e) {
      console.error('deleteJob error:', e);
    }
  },

  // ── APPLICATIONS ────────────────────────────────────────────
  getApps() {
    try {
      const a = localStorage.getItem("ih_apps");
      return a && Validate.safeJSON(a) ? JSON.parse(a) : [];
    } catch(e) {
      console.error('getApps error:', e);
      return [];
    }
  },

  saveApps(apps) {
    try {
      localStorage.setItem("ih_apps", JSON.stringify(apps));
      DataBackup.autoBackup();
    } catch(e) {
      console.error('saveApps error:', e);
    }
  },

  hasApplied(studentId, jobId) {
    try {
      return this.getApps().some(a => a.studentId === studentId && a.jobId === jobId);
    } catch(e) {
      console.warn('hasApplied error:', e);
      return false;
    }
  },

  hasActiveApplication(studentId, jobId) {
    try {
      return this.getApps().some(a => a.studentId === studentId && a.jobId === jobId && a.status !== 'Rejected');
    } catch(e) {
      console.warn('hasActiveApplication error:', e);
      return false;
    }
  },

  apply(studentId, jobId, coverNote="") {
    try {
      if (this.hasActiveApplication(studentId, jobId)) return false;
      const apps = this.getApps();
      apps.push({
        id:         "app_"+Date.now(),
        studentId,
        jobId,
        coverNote,
        status:     "Applied",
        appliedAt:  new Date().toISOString(),
      });
      this.saveApps(apps);
      return true;
    } catch(e) {
      console.error('apply error:', e);
      return false;
    }
  },

  getStudentApps(studentId) {
    try {
      return this.getApps()
        .filter(a => a.studentId === studentId)
        .map(a => ({ ...a, job: this.getJobById(a.jobId) }))
        .filter(a => a.job);
    } catch(e) {
      console.warn('getStudentApps error:', e);
      return [];
    }
  },

  getAppsForJob(jobId) {
    try {
      return this.getApps()
        .filter(a => a.jobId === jobId)
        .map(a => ({ ...a, student: a.student || this.findUserById(a.studentId), resume: this.getResume(a.studentId) }));
    } catch(e) {
      console.warn('getAppsForJob error:', e);
      return [];
    }
  },

  getAppsForRecruiter(recruiterId) {
    try {
      const rJobs = this.getJobsByRecruiter(recruiterId).map(j => j.id);
      return this.getApps()
        .filter(a => rJobs.includes(a.jobId))
        .map(a => ({
          ...a,
          // Use student data from API if available, otherwise look up from database
          student:  a.student || this.findUserById(a.studentId),
          job:      this.getJobById(a.jobId),
          resume:   this.getResume(a.studentId),
        }));
    } catch(e) {
      console.warn('getAppsForRecruiter error:', e);
      return [];
    }
  },

  updateAppStatus(appId, status) {
    try {
      const apps = this.getApps().map(a => a.id===appId ? {...a, status} : a);
      this.saveApps(apps);
    } catch(e) {
      console.error('updateAppStatus error:', e);
    }
  },

  // ── RESUMES ─────────────────────────────────────────────────
  // Stored as { studentId, fileName, fileData (base64), uploadedAt, parsed:{} }
  getResumes() {
    try {
      const r = localStorage.getItem("ih_resumes");
      return r && Validate.safeJSON(r) ? JSON.parse(r) : [];
    } catch(e) {
      console.error('getResumes error:', e);
      return [];
    }
  },

  saveResumes(r) {
    try {
      localStorage.setItem("ih_resumes", JSON.stringify(r));
      DataBackup.autoBackup();
    } catch(e) {
      console.error('saveResumes error:', e);
    }
  },

  getResume(studentId) {
    try {
      return studentId ? this.getResumes().find(r => r.studentId === studentId) || null : null;
    } catch(e) {
      console.warn('getResume error:', e);
      return null;
    }
  },

  saveResume(studentId, fileName, fileData, parsed={}) {
    try {
      const resumes = this.getResumes().filter(r => r.studentId !== studentId);
      resumes.push({ studentId, fileName, fileData, parsed, uploadedAt: new Date().toISOString() });
      this.saveResumes(resumes);
    } catch(e) {
      console.error('saveResume error:', e);
    }
  },

  deleteResume(studentId) {
    try {
      this.saveResumes(this.getResumes().filter(r => r.studentId !== studentId));
    } catch(e) {
      console.error('deleteResume error:', e);
    }
  },

  // ── SAVED JOBS ──────────────────────────────────────────────
  // Stored as { studentId, jobId, savedAt }
  getSavedJobsList() {
    try {
      const s = localStorage.getItem("ih_saved_jobs");
      return s && Validate.safeJSON(s) ? JSON.parse(s) : [];
    } catch(e) {
      console.error('getSavedJobsList error:', e);
      return [];
    }
  },

  saveSavedJobsList(list) {
    try {
      localStorage.setItem("ih_saved_jobs", JSON.stringify(list));
      DataBackup.autoBackup();
    } catch(e) {
      console.error('saveSavedJobsList error:', e);
    }
  },

  saveJob(studentId, jobId) {
    try {
      const saved = this.getSavedJobsList();
      if (!saved.find(s => s.studentId === studentId && s.jobId === jobId)) {
        saved.push({ studentId, jobId, savedAt: new Date().toISOString() });
        this.saveSavedJobsList(saved);
      }
    } catch(e) {
      console.error('saveJob error:', e);
    }
  },

  unsaveJob(studentId, jobId) {
    try {
      const saved = this.getSavedJobsList().filter(s => !(s.studentId === studentId && s.jobId === jobId));
      this.saveSavedJobsList(saved);
    } catch(e) {
      console.error('unsaveJob error:', e);
    }
  },

  isSaved(studentId, jobId) {
    try {
      return this.getSavedJobsList().some(s => s.studentId === studentId && s.jobId === jobId);
    } catch(e) {
      console.error('isSaved error:', e);
      return false;
    }
  },

  getSavedJobs(studentId) {
    try {
      const saved = this.getSavedJobsList()
        .filter(s => s.studentId === studentId)
        .map(s => this.getJobById(s.jobId))
        .filter(j => j);
      return saved;
    } catch(e) {
      console.error('getSavedJobs error:', e);
      return [];
    }
  },

  // ── INTERVIEWS ──────────────────────────────────────────────
  // Stored as { id, recruiterId, studentId, jobId, candidateName, candidateEmail, type, date, time, link, status, createdAt }
  getInterviews() {
    try {
      const i = localStorage.getItem("ih_interviews");
      return i && Validate.safeJSON(i) ? JSON.parse(i) : [];
    } catch(e) {
      console.error('getInterviews error:', e);
      return [];
    }
  },

  saveInterviews(interviews) {
    try {
      localStorage.setItem("ih_interviews", JSON.stringify(interviews));
      DataBackup.autoBackup();
    } catch(e) {
      console.error('saveInterviews error:', e);
    }
  },

  createInterview(recruiterId, studentId, jobId, candidateName, candidateEmail, type, date, time, link="") {
    try {
      const interviews = this.getInterviews();
      const interview = {
        id: "int_"+Date.now(),
        recruiterId,
        studentId,
        jobId,
        candidateName,
        candidateEmail,
        type,
        date,
        time,
        link,
        status: "Scheduled",
        createdAt: new Date().toISOString(),
      };
      interviews.push(interview);
      this.saveInterviews(interviews);
      return interview;
    } catch(e) {
      console.error('createInterview error:', e);
      return null;
    }
  },

  getInterviewsForCandidate(studentId) {
    try {
      return this.getInterviews()
        .filter(i => i.studentId === studentId)
        .map(i => ({ ...i, job: this.getJobById(i.jobId), recruiter: this.findUserById(i.recruiterId) }));
    } catch(e) {
      console.warn('getInterviewsForCandidate error:', e);
      return [];
    }
  },

  getInterviewsForRecruiter(recruiterId) {
    try {
      return this.getInterviews()
        .filter(i => i.recruiterId === recruiterId)
        .map(i => ({ ...i, job: this.getJobById(i.jobId), student: this.findUserById(i.studentId) }));
    } catch(e) {
      console.warn('getInterviewsForRecruiter error:', e);
      return [];
    }
  },

  updateInterviewStatus(interviewId, status) {
    try {
      const interviews = this.getInterviews().map(i => i.id === interviewId ? { ...i, status } : i);
      this.saveInterviews(interviews);
    } catch(e) {
      console.error('updateInterviewStatus error:', e);
    }
  },

  deleteInterview(interviewId) {
    try {
      this.saveInterviews(this.getInterviews().filter(i => i.id !== interviewId));
    } catch(e) {
      console.error('deleteInterview error:', e);
    }
  },

  // ── OFFERS ──────────────────────────────────────────────
  getOffers() {
    try {
      const o = localStorage.getItem("ih_offers");
      return o && Validate.safeJSON(o) ? JSON.parse(o) : [];
    } catch(e) {
      console.error('getOffers error:', e);
      return [];
    }
  },

  saveOffers(offers) {
    try {
      localStorage.setItem("ih_offers", JSON.stringify(offers));
      DataBackup.autoBackup();
    } catch(e) {
      console.error('saveOffers error:', e);
    }
  },

  getOffersForStudent(studentId) {
    try {
      return this.getOffers()
        .filter(o => o.studentId === studentId)
        .map(o => ({ ...o, job: this.getJobById(o.jobId) }));
    } catch(e) {
      console.warn('getOffersForStudent error:', e);
      return [];
    }
  },

  getOffersForRecruiter(recruiterId) {
    try {
      return this.getOffers()
        .filter(o => o.recruiterId === recruiterId)
        .map(o => ({ ...o, job: this.getJobById(o.jobId), student: this.findUserById(o.studentId) }));
    } catch(e) {
      console.warn('getOffersForRecruiter error:', e);
      return [];
    }
  },

  updateOfferStatus(offerId, status) {
    try {
      const offers = this.getOffers().map(o => o.id === offerId ? { ...o, status } : o);
      this.saveOffers(offers);
    } catch(e) {
      console.error('updateOfferStatus error:', e);
    }
  },

  deleteOffer(offerId) {
    try {
      this.saveOffers(this.getOffers().filter(o => o.id !== offerId));
    } catch(e) {
      console.error('deleteOffer error:', e);
    }
  },
};

// ── Auth shorthand ───────────────────────────────────────────
const Auth = {
  current() { 
    // Get session from sessionStorage
    return DB.getSession();
  },
  
  login(email, pw) {
    const u = DB.verifyUser(email, pw);
    if (u) {
      DB.setSession(u);
      // Also save to localStorage as backup for session recovery
      try {
        localStorage.setItem("ih_last_login", JSON.stringify({
          email: u.email,
          role: u.role,
          timestamp: new Date().toISOString()
        }));
      } catch(e) {
        console.warn('Could not save last login:', e);
      }
    }
    return u;
  },
  
  logout() { 
    DB.clearSession();
    try {
      localStorage.removeItem("ih_last_login");
    } catch(e) {
      console.warn('Could not clear last login:', e);
    }
  },
  
  isStudent() { return this.current()?.role === "student"; },
  isRecruiter() { return this.current()?.role === "recruiter"; },
};
