import { createStore } from 'vuex';
import VuexPersist from 'vuex-persist';

const vuexPersist = new VuexPersist({
    key: 'faltec',
    storage: localStorage,
    filter: (mutation) => mutation.type !== 'resetState',
});

const getDefaultState = () => ({
    userName: "",
    userId: "",
    startline: "",
    gps: "",
    can: "",
});

const store = createStore({
    state: getDefaultState(),
    mutations: {
        setUserName(state, name) {
            state.userName = name;
        },
        setUserId(state, id) {
            state.userId = id;
        },
        setStartline(state, startline) {
            state.startline = startline;
        },
        setGps(state, gps) {
            state.gps = gps;
        },
        setCan(state, can) {
            state.can = can;
        },
        resetState(state) {
            Object.assign(state, getDefaultState());
            localStorage.clear('faltec');
        },
    },
    actions: {
        setUserName({ commit }, name) {
            commit("setUserName", name);
        },
        setUserId({ commit }, id) {
            commit("setUserId", id);
        },
        setStartline({ commit }, startline) {
            commit("setStartline", startline);
        },
        setGps({ commit }, gps) {
            commit("setGps", gps);
        },
        setCan({ commit }, can) {
            commit("setCan", can);
        },
        resetState({ commit }) {
            commit("resetState");
        },
    },
    getters: {
        userName: (state) => state.userName,
        userId: (state) => state.userId,
        startline: (state) => state.startline,
        gps: (state) => state.gps,
        can: (state) => state.can,
    },
    plugins: [vuexPersist.plugin],
});

export default store;
