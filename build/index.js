"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = __importDefault(require("express"));
// import cors from 'cors';
const app = (0, express_1.default)();
const Port = 8080;
app.use(express_1.default.json());
// app.use(cors());
app.get("/ping", (_req, res) => {
    try {
        console.log("saludos!");
        res.send("pong!");
    }
    catch (error) {
        console.log("🚀 ~ app.get ~ error:", error);
    }
});
app.listen(Port, () => {
    console.log(`Server is running on port ${Port}`);
});
