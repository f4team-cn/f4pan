import asyncio
import json
import uuid
import base64
from datetime import datetime, timedelta
from fastapi import FastAPI, WebSocket
from playwright.async_api import async_playwright
from fastapi.middleware.cors import CORSMiddleware

app = FastAPI()

origins = [
    "*"
]

app.add_middleware(
    CORSMiddleware,
    allow_origins=origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


async def check_login_status(page):
    success_element = await page.query_selector('xpath=//*[@id="app"]/div[1]/div/div[1]/div/div[1]')
    return success_element


def convert_cookies_to_key_value_pairs(json_data):
    key_value_pairs = [f"{cookie['name']}={cookie['value']}" for cookie in json_data]
    return ";".join(key_value_pairs)


async def getAccessToken(localstate):
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=False)
        context = await browser.new_context(storage_state=localstate)
        page = await context.new_page()
        await page.goto('https://openapi.baidu.com/oauth/2.0/authorize?client_id=IlLqBbU3GjQ0t46TRwFateTprHWl39zF&response_type=token&redirect_uri=oob&scope=basic,netdisk')
        storage = await context.storage_state()
        await browser.close()
        #在page.url参数中获取access_token
        access_token = page.url.split("access_token=")[1].split("&")[0]
        return access_token, storage

async def websocket_endpoint(websocket: WebSocket):
    await websocket.accept()
    req_id = str(uuid.uuid4())
    qr_status = {req_id: {"status": "waitingForCommand", "created_at": datetime.now()}}

    async def start_login_process():
        nonlocal qr_status
        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=False)
            context = await browser.new_context()
            page = await context.new_page()
            await page.goto("https://passport.baidu.com/v2/?login")
            selector = await page.query_selector('xpath=//*[@id="TANGRAM__PSP_3__QrcodeMain"]/img')
            pic = await selector.screenshot()
            await websocket.send_text(json.dumps({"method": "qrCodeGenerated", "data": base64.b64encode(pic).decode()}))
            end_time = qr_status[req_id]["created_at"] + timedelta(minutes=1)
            while datetime.now() < end_time:
                is_logged_in = await check_login_status(page)
                if is_logged_in:
                    storage_state = await context.storage_state()
                    await page.goto('https://pan.baidu.com')
                    cookie = await context.cookies(page.url)
                    cookies = convert_cookies_to_key_value_pairs(cookie)
                    await websocket.send_text(json.dumps({"method": "loginSuccess", "data": storage_state, "cookies": cookies}))
                    await context.close()
                    qr_status = {req_id: {"status": "waitingForCommand", "created_at": datetime.now()}}
                    break
                await asyncio.sleep(1)
                await websocket.send_text(
                    json.dumps({"method": "countdown", "remaining": int((end_time - datetime.now()).total_seconds())}))

            if not is_logged_in:
                await context.close()
                qr_status = {req_id: {"status": "waitingForCommand", "created_at": datetime.now()}}
                await websocket.send_text(json.dumps({"method": "loginTimeout"}))

    while True:
        data = await websocket.receive_text()
        data_json = json.loads(data)
        if data_json["method"] == "getQrcode" and qr_status[req_id]["status"] == "waitingForCommand":
            qr_status[req_id]["status"] = "processing"
            asyncio.create_task(start_login_process())
            await websocket.send_text(json.dumps({"method": "processing"}))
        if data_json["method"] == "getAccess":
            if not data_json["localstate"]:
                await websocket.send_text(json.dumps({"method": "getAccessFail", "data": "should have lcatstate"}))
            else:
                access, local = await getAccessToken(data_json["localstate"])
                await websocket.send_text(json.dumps({"method": "getAccessSuccess", "data": {"accss_token": access, "localstate": local}}))


@app.websocket("/ws")
async def websocket_endpoint_ws(websocket: WebSocket):
    await websocket_endpoint(websocket)


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8001)
